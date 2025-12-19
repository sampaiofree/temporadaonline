<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Models\PartidaDenuncia;
use App\Services\PartidaStateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartidaActionsController extends Controller
{
    public function __construct(private readonly PartidaStateService $state)
    {
    }

    public function checkin(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);

        $partida->loadMissing(['mandante', 'visitante']);
        $this->state->assertActionAllowed($partida, ['confirmada', 'em_andamento']);

        if (! $partida->scheduled_at) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Partida sem horário definido.'],
            ]);
        }

        $now = Carbon::now('UTC');
        $startWindow = $partida->scheduled_at->copy()->subMinutes(30);
        $endWindow = $partida->scheduled_at->copy()->addMinutes(15);

        if ($now->lt($startWindow) || $now->gt($endWindow)) {
            throw ValidationException::withMessages([
                'checkin' => ['Check-in permitido de 30min antes até 15min depois do horário.'],
            ]);
        }

        if ($user->id === $partida->mandante->user_id) {
            $partida->checkin_mandante_at = $now;
        } else {
            $partida->checkin_visitante_at = $now;
        }

        $bothChecked = $partida->checkin_mandante_at && $partida->checkin_visitante_at;
        $partida->save();

        if ($bothChecked && $partida->estado === 'confirmada') {
            $this->state->transitionTo(
                $partida,
                'em_andamento',
                [],
                'inicio_partida',
                $user->id,
                ['auto_start' => true]
            );
        }

        return response()->json([
            'message' => 'Check-in confirmado.',
            'checkin_mandante_at' => $partida->checkin_mandante_at?->toIso8601String(),
            'checkin_visitante_at' => $partida->checkin_visitante_at?->toIso8601String(),
            'estado' => $partida->estado,
        ]);
    }

    public function registrarPlacar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);
        $this->state->assertActionAllowed($partida, ['em_andamento']);

        $data = $request->validate([
            'placar_mandante' => ['required', 'integer', 'min:0'],
            'placar_visitante' => ['required', 'integer', 'min:0'],
        ]);

        $payload = [
            'placar_mandante' => (int) $data['placar_mandante'],
            'placar_visitante' => (int) $data['placar_visitante'],
        ];

        $this->state->transitionTo(
            $partida,
            'finalizada',
            $payload,
            'finalizacao_partida',
            $user->id,
            $payload
        );

        return response()->json([
            'message' => 'Placar registrado.',
            'estado' => $partida->estado,
            'placar_mandante' => $partida->placar_mandante,
            'placar_visitante' => $partida->placar_visitante,
        ]);
    }

    public function denunciar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);
        $this->state->assertActionAllowed($partida, ['em_andamento']);

        $data = $request->validate([
            'motivo' => ['required', 'in:conduta_antidesportiva,escala_irregular,conexao,outro'],
            'descricao' => ['nullable', 'string', 'max:500'],
        ]);

        PartidaDenuncia::create([
            'partida_id' => $partida->id,
            'user_id' => $user->id,
            'motivo' => $data['motivo'],
            'descricao' => $data['descricao'] ?? null,
        ]);

        return response()->json([
            'message' => 'Denúncia registrada.',
        ]);
    }

    private function assertParticipante(int $userId, Partida $partida): void
    {
        $partida->loadMissing(['mandante', 'visitante']);
        $mandanteUserId = $partida->mandante->user_id;
        $visitanteUserId = $partida->visitante->user_id;

        if (! in_array($userId, [$mandanteUserId, $visitanteUserId], true)) {
            abort(403, 'Apenas participantes podem acessar esta partida.');
        }
    }
}
