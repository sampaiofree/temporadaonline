<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Partida;
use App\Models\Liga;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaPartidasController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);

        $partidas = Partida::query()
            ->with(['mandante.user', 'visitante.user'])
            ->where('liga_id', $liga->id)
            ->orderByRaw('scheduled_at IS NULL, scheduled_at ASC, created_at DESC')
            ->get()
            ->map(function (Partida $partida) use ($liga) {
                $tz = $liga->timezone ?? 'UTC';

                return [
                    'id' => $partida->id,
                    'mandante' => $partida->mandante?->nome,
                    'visitante' => $partida->visitante?->nome,
                    'mandante_id' => $partida->mandante_id,
                    'visitante_id' => $partida->visitante_id,
                    'mandante_user_id' => $partida->mandante?->user_id,
                    'visitante_user_id' => $partida->visitante?->user_id,
                    'mandante_nickname' => $partida->mandante?->user?->nickname ?? $partida->mandante?->user?->name,
                    'visitante_nickname' => $partida->visitante?->user?->nickname ?? $partida->visitante?->user?->name,
                    'mandante_logo' => $partida->mandante?->escudo_url,
                    'visitante_logo' => $partida->visitante?->escudo_url,
                    'estado' => $partida->estado,
                    'scheduled_at' => $partida->scheduled_at ? $partida->scheduled_at->timezone($tz)->toIso8601String() : null,
                    'forced_by_system' => (bool) $partida->forced_by_system,
                    'sem_slot_disponivel' => (bool) $partida->sem_slot_disponivel,
                    'placar_mandante' => $partida->placar_mandante,
                    'placar_visitante' => $partida->placar_visitante,
                    'wo_para_user_id' => $partida->wo_para_user_id,
                    'wo_motivo' => $partida->wo_motivo,
                    'checkin_mandante_at' => $partida->checkin_mandante_at?->timezone($tz)->toIso8601String(),
                    'checkin_visitante_at' => $partida->checkin_visitante_at?->timezone($tz)->toIso8601String(),
                ];
            })
            ->values();

        $minhasPartidas = [];
        if ($clube) {
            $minhasPartidas = $partidas->filter(function ($p) use ($clube) {
                return (int) $p['mandante_id'] === (int) $clube->id || (int) $p['visitante_id'] === (int) $clube->id;
            })->map(function ($p) use ($clube) {
                $p['is_mandante'] = (int) $p['mandante_id'] === (int) $clube->id;
                $p['is_visitante'] = (int) $p['visitante_id'] === (int) $clube->id;

                return $p;
            })->values()->all();
        }

        return view('liga_partidas', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'jogo' => $liga->jogo?->nome,
                'timezone' => $liga->timezone,
            ],
            'clube' => $clube ? [
                'id' => $clube->id,
                'nome' => $clube->nome,
                'user_id' => $clube->user_id,
            ] : null,
            'minhas_partidas' => $minhasPartidas,
            'todas_partidas' => $partidas->map(function ($p) use ($clube) {
                if ($clube) {
                    $p['is_mandante'] = (int) $p['mandante_id'] === (int) $clube->id;
                    $p['is_visitante'] = (int) $p['visitante_id'] === (int) $clube->id;
                }

                return $p;
            })->all(),
            'appContext' => $this->makeAppContext($liga, $clube, 'partidas'),
        ]);
    }
}
