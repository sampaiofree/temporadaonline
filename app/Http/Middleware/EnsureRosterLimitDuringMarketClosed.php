<?php

namespace App\Http\Middleware;

use App\Models\Liga;
use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRosterLimitDuringMarketClosed
{
    private const CLOSED_MARKET_LIMIT = 18;

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->resolveContext($request);

        if (! $context) {
            return $next($request);
        }

        [$liga, $clubeId] = $context;

        if (! LigaPeriodo::activeRangeForLiga($liga)) {
            return $next($request);
        }

        $activeCount = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $clubeId)
            ->where('ativo', true)
            ->count();

        if ($activeCount <= self::CLOSED_MARKET_LIMIT) {
            return $next($request);
        }

        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        return $this->blockAccess($request, $liga, $activeCount);
    }

    private function isAllowedRoute(Request $request): bool
    {
        if ($request->routeIs('minha_liga.meu_elenco')) {
            return $this->hasLigaId($request);
        }

        return $request->routeIs('elenco.venderMercado');
    }

    private function blockAccess(Request $request, Liga $liga, int $activeCount): Response
    {
        $message = "Mercado fechado. Seu clube tem {$activeCount} jogadores ativos. Venda ate ficar com "
            . self::CLOSED_MARKET_LIMIT . '.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 423);
        }

        return redirect()->route('minha_liga.meu_elenco', ['liga_id' => $liga->id]);
    }

    private function resolveContext(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $ligaId = $request->query('liga_id') ?? $request->input('liga_id');
        if ($ligaId) {
            $liga = $user->ligas()->where('ligas.id', $ligaId)->first();
            if (! $liga) {
                return null;
            }

            $clube = $user->clubesLiga()->where('liga_id', $liga->id)->first();
            if (! $clube) {
                return null;
            }

            return [$liga, (int) $clube->id];
        }

        $elencoParam = $request->route('elenco');
        if (! $elencoParam) {
            return $this->resolveBlockedLeague($user);
        }

        $entry = $elencoParam instanceof LigaClubeElenco
            ? $elencoParam->loadMissing('ligaClube')
            : LigaClubeElenco::query()
                ->with('ligaClube')
                ->find($elencoParam);

        if (! $entry || ! $entry->ligaClube) {
            return null;
        }

        if ((int) $entry->ligaClube->user_id !== (int) $user->id) {
            return null;
        }

        $liga = $user->ligas()->where('ligas.id', $entry->liga_id)->first();
        if (! $liga) {
            return null;
        }

        return [$liga, (int) $entry->liga_clube_id];
    }

    private function hasLigaId(Request $request): bool
    {
        return (bool) ($request->query('liga_id') ?? $request->input('liga_id'));
    }

    private function resolveBlockedLeague($user): ?array
    {
        $clubes = $user->clubesLiga()->with('liga')->get(['id', 'liga_id', 'user_id']);

        foreach ($clubes as $clube) {
            $liga = $clube->liga;

            if (! $liga) {
                continue;
            }

            if (! LigaPeriodo::activeRangeForLiga($liga)) {
                continue;
            }

            $activeCount = LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $clube->id)
                ->where('ativo', true)
                ->count();

            if ($activeCount > self::CLOSED_MARKET_LIMIT) {
                return [$liga, (int) $clube->id];
            }
        }

        return null;
    }
}
