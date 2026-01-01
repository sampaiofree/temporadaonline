<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Models\PartidaAvaliacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingActionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $clubIds = $user->clubesLiga()->pluck('id');

        if ($clubIds->isEmpty()) {
            return response()->json([
                'confirmations' => [],
                'evaluations' => [],
                'disabled' => false,
            ]);
        }

        $partidas = Partida::query()
            ->with(['mandante.user', 'visitante.user'])
            ->whereIn('estado', ['placar_registrado', 'placar_confirmado', 'em_reclamacao', 'finalizada'])
            ->where(function ($query) use ($clubIds): void {
                $query->whereIn('mandante_id', $clubIds)
                    ->orWhereIn('visitante_id', $clubIds);
            })
            ->orderByRaw('placar_registrado_em IS NULL, placar_registrado_em ASC, created_at ASC')
            ->get();

        if ($partidas->isEmpty()) {
            return response()->json([
                'confirmations' => [],
                'evaluations' => [],
                'disabled' => false,
            ]);
        }

        $avaliacoes = PartidaAvaliacao::query()
            ->whereIn('partida_id', $partidas->pluck('id'))
            ->where('avaliador_user_id', $user->id)
            ->get()
            ->keyBy('partida_id');

        $payload = $partidas->map(function (Partida $partida) use ($clubIds, $avaliacoes) {
            $isMandante = $clubIds->contains($partida->mandante_id);
            $isVisitante = $clubIds->contains($partida->visitante_id);
            $avaliacao = $avaliacoes->get($partida->id);

            return [
                'id' => $partida->id,
                'estado' => $partida->estado,
                'mandante' => $partida->mandante?->nome,
                'visitante' => $partida->visitante?->nome,
                'mandante_nickname' => $partida->mandante?->user?->nickname ?? $partida->mandante?->user?->name,
                'visitante_nickname' => $partida->visitante?->user?->nickname ?? $partida->visitante?->user?->name,
                'placar_mandante' => $partida->placar_mandante,
                'placar_visitante' => $partida->placar_visitante,
                'placar_registrado_por' => $partida->placar_registrado_por,
                'placar_registrado_em' => $partida->placar_registrado_em?->toIso8601String(),
                'is_mandante' => $isMandante,
                'is_visitante' => $isVisitante,
                'avaliacao' => $avaliacao ? [
                    'nota' => $avaliacao->nota,
                ] : null,
            ];
        });

        $confirmations = $payload
            ->filter(function (array $item) use ($user): bool {
                if ($item['estado'] !== 'placar_registrado') {
                    return false;
                }

                return (int) ($item['placar_registrado_por'] ?? 0) !== (int) $user->id;
            })
            ->values()
            ->all();

        $evaluations = $payload
            ->filter(function (array $item): bool {
                if (! in_array($item['estado'], ['placar_registrado', 'placar_confirmado', 'em_reclamacao', 'finalizada'], true)) {
                    return false;
                }

                return ! $item['avaliacao'];
            })
            ->values()
            ->all();

        return response()->json([
            'confirmations' => $confirmations,
            'evaluations' => $evaluations,
            'disabled' => false,
        ]);
    }
}
