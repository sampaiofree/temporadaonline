<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Models\PartidaAvaliacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartidaAvaliacaoController extends Controller
{
    public function store(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $partida->loadMissing(['mandante.user', 'visitante.user']);

        $mandanteUserId = $partida->mandante?->user_id;
        $visitanteUserId = $partida->visitante?->user_id;

        if (! $mandanteUserId || ! $visitanteUserId) {
            abort(403, 'Partida sem participantes validos.');
        }

        if (! in_array($user->id, [$mandanteUserId, $visitanteUserId], true)) {
            abort(403, 'Apenas participantes podem avaliar esta partida.');
        }

        if (! in_array($partida->estado, ['placar_registrado', 'placar_confirmado', 'em_reclamacao', 'finalizada'], true)) {
            abort(403, 'Avaliacao indisponivel para este estado.');
        }

        $exists = PartidaAvaliacao::query()
            ->where('partida_id', $partida->id)
            ->where('avaliador_user_id', $user->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'avaliacao' => ['Avaliacao ja enviada para esta partida.'],
            ]);
        }

        $data = $request->validate([
            'nota' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $avaliadoUserId = $user->id === $mandanteUserId ? $visitanteUserId : $mandanteUserId;

        $avaliacao = PartidaAvaliacao::create([
            'partida_id' => $partida->id,
            'avaliador_user_id' => $user->id,
            'avaliado_user_id' => $avaliadoUserId,
            'nota' => (int) $data['nota'],
        ]);

        return response()->json([
            'message' => 'Avaliacao registrada.',
            'nota' => $avaliacao->nota,
            'avaliado_user_id' => $avaliacao->avaliado_user_id,
        ]);
    }
}
