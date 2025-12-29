<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Partida;
use App\Services\PartidaPlacarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LigaPartidasController extends Controller
{
    use ResolvesLiga;

    public function __construct(private readonly PartidaPlacarService $placarService)
    {
    }

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);
        $clube?->loadMissing('escudo');

        $partidasCollection = collect();

        if ($clube) {
            $partidasCollection = Partida::query()
                ->with(['mandante.user', 'visitante.user', 'mandante.escudo', 'visitante.escudo'])
                ->where('liga_id', $liga->id)
                ->where(function ($query) use ($clube): void {
                    $query->where('mandante_id', $clube->id)
                        ->orWhere('visitante_id', $clube->id);
                })
                ->orderByRaw('scheduled_at IS NULL, scheduled_at ASC, created_at DESC')
                ->get();
        }

        $partidasCollection->each(fn (Partida $partida) => $this->placarService->maybeAutoConfirm($partida));

        $partidas = $partidasCollection
            ->map(function (Partida $partida) use ($liga, $clube) {
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
                    'mandante_logo' => $this->resolveEscudoUrl($partida->mandante?->escudo?->clube_imagem),
                    'visitante_logo' => $this->resolveEscudoUrl($partida->visitante?->escudo?->clube_imagem),
                    'estado' => $partida->estado,
                    'scheduled_at' => $partida->scheduled_at ? $partida->scheduled_at->timezone($tz)->toIso8601String() : null,
                    'forced_by_system' => (bool) $partida->forced_by_system,
                    'sem_slot_disponivel' => (bool) $partida->sem_slot_disponivel,
                    'placar_mandante' => $partida->placar_mandante,
                    'placar_visitante' => $partida->placar_visitante,
                    'placar_registrado_por' => $partida->placar_registrado_por,
                    'placar_registrado_em' => $partida->placar_registrado_em?->toIso8601String(),
                    'wo_para_user_id' => $partida->wo_para_user_id,
                    'wo_motivo' => $partida->wo_motivo,
                    'checkin_mandante_at' => $partida->checkin_mandante_at?->timezone($tz)->toIso8601String(),
                    'checkin_visitante_at' => $partida->checkin_visitante_at?->timezone($tz)->toIso8601String(),
                    'is_mandante' => $clube ? (int) $partida->mandante_id === (int) $clube->id : false,
                    'is_visitante' => $clube ? (int) $partida->visitante_id === (int) $clube->id : false,
                ];
            })
            ->values()
            ->all();

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
                'escudo_url' => $clube->escudo?->clube_imagem
                    ? $this->resolveEscudoUrl($clube->escudo->clube_imagem)
                    : null,
            ] : null,
            'partidas' => $partidas,
            'appContext' => $this->makeAppContext($liga, $clube, 'partidas'),
        ]);
    }

    public function finalizar(Request $request, Partida $partida): View
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);
        $clube?->loadMissing('escudo');

        if (! $clube) {
            abort(403, 'Clube não encontrado.');
        }

        if ((int) $partida->liga_id !== (int) $liga->id) {
            abort(404, 'Partida não encontrada.');
        }

        if ((int) $partida->visitante_id !== (int) $clube->id) {
            abort(403, 'Somente o visitante pode finalizar esta partida.');
        }

        if ($partida->estado !== 'confirmada') {
            abort(403, 'Partida não está confirmada.');
        }

        $partida->loadMissing(['mandante.user', 'visitante.user', 'mandante.escudo', 'visitante.escudo']);

        $tz = $liga->timezone ?? 'UTC';

        $payload = [
            'id' => $partida->id,
            'mandante' => $partida->mandante?->nome,
            'visitante' => $partida->visitante?->nome,
            'mandante_id' => $partida->mandante_id,
            'visitante_id' => $partida->visitante_id,
            'mandante_user_id' => $partida->mandante?->user_id,
            'visitante_user_id' => $partida->visitante?->user_id,
            'mandante_nickname' => $partida->mandante?->user?->nickname ?? $partida->mandante?->user?->name,
            'visitante_nickname' => $partida->visitante?->user?->nickname ?? $partida->visitante?->user?->name,
            'mandante_logo' => $this->resolveEscudoUrl($partida->mandante?->escudo?->clube_imagem),
            'visitante_logo' => $this->resolveEscudoUrl($partida->visitante?->escudo?->clube_imagem),
            'estado' => $partida->estado,
            'scheduled_at' => $partida->scheduled_at ? $partida->scheduled_at->timezone($tz)->toIso8601String() : null,
            'placar_mandante' => $partida->placar_mandante,
            'placar_visitante' => $partida->placar_visitante,
            'placar_registrado_por' => $partida->placar_registrado_por,
            'placar_registrado_em' => $partida->placar_registrado_em?->toIso8601String(),
            'checkin_mandante_at' => $partida->checkin_mandante_at?->timezone($tz)->toIso8601String(),
            'checkin_visitante_at' => $partida->checkin_visitante_at?->timezone($tz)->toIso8601String(),
        ];

        return view('liga_partida_finalizar', [
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
                'escudo_url' => $clube->escudo?->clube_imagem
                    ? $this->resolveEscudoUrl($clube->escudo->clube_imagem)
                    : null,
            ] : null,
            'partida' => $payload,
            'appContext' => $this->makeAppContext($liga, $clube, 'partidas'),
        ]);
    }

    private function resolveEscudoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
