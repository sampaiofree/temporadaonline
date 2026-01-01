<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Models\PartidaAvaliacao;
use App\Models\PartidaDenuncia;
use App\Models\ReclamacaoPartida;
use App\Services\PartidaStateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartidaActionsController extends Controller
{
    public function __construct(
        private readonly PartidaStateService $state,
    ) {
    }

    public function checkin(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);

        $partida->loadMissing(['mandante', 'visitante']);
        $this->state->assertActionAllowed($partida, ['confirmada']);

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
                'confirmada',
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
        $this->state->assertActionAllowed($partida, ['confirmada']);

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
            'placar_registrado',
            array_merge($payload, [
                'placar_registrado_por' => $user->id,
                'placar_registrado_em' => Carbon::now('UTC'),
            ]),
            'placar_registrado',
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

    public function confirmarPlacar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);

        $this->state->assertActionAllowed($partida, ['placar_registrado']);

        if ($partida->placar_registrado_por === $user->id) {
            abort(403, 'Somente o adversário pode confirmar o placar.');
        }

        $avaliou = PartidaAvaliacao::query()
            ->where('partida_id', $partida->id)
            ->where('avaliador_user_id', $user->id)
            ->exists();

        if (! $avaliou) {
            throw ValidationException::withMessages([
                'avaliacao' => ['Avalie o adversario antes de confirmar o placar.'],
            ]);
        }

        $payload = [
            'placar_mandante' => $partida->placar_mandante ?? 0,
            'placar_visitante' => $partida->placar_visitante ?? 0,
        ];

        $this->state->transitionTo(
            $partida,
            'placar_confirmado',
            [],
            'placar_confirmado',
            $user->id,
            $payload
        );

        return response()->json([
            'message' => 'Placar confirmado.',
            'estado' => $partida->estado,
            'placar_mandante' => $partida->placar_mandante,
            'placar_visitante' => $partida->placar_visitante,
        ]);
    }

    public function reclamar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);

        $this->state->assertActionAllowed($partida, ['placar_registrado']);

        if ($partida->placar_registrado_por === $user->id) {
            abort(403, 'O registrante não pode contestar o próprio placar.');
        }

        $data = $request->validate([
            'motivo' => ['required', 'in:placar_incorreto,wo_indevido,queda_conexao,outro'],
            'descricao' => ['required', 'string', 'max:1000'],
            'imagem' => ['nullable', 'string', 'max:600'],
        ]);

        $reclamacao = ReclamacaoPartida::create([
            'partida_id' => $partida->id,
            'user_id' => $user->id,
            'motivo' => $data['motivo'],
            'descricao' => $data['descricao'],
            'imagem' => $data['imagem'] ?? null,
            'status' => 'aberta',
        ]);

        $this->state->transitionTo(
            $partida,
            'em_reclamacao',
            [],
            'placar_reclamacao',
            $user->id,
            [
                'motivo' => $data['motivo'],
                'reclamacao_id' => $reclamacao->id,
            ],
        );

        return response()->json([
            'message' => 'Reclamação registrada.',
            'estado' => $partida->estado,
            'reclamacao_id' => $reclamacao->id,
        ]);
    }

    public function desistir(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);
        $partida->loadMissing(['mandante.user', 'visitante.user']);

        $this->state->assertActionAllowed($partida, ['confirmacao_necessaria', 'agendada', 'confirmada']);

        $now = Carbon::now('UTC');

        if ($partida->scheduled_at) {
            if ($now->greaterThanOrEqualTo($partida->scheduled_at)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => ['Não é possível desistir após o horário da partida.'],
                ]);
            }

            $limit = $partida->scheduled_at->copy()->subMinutes(60);

            if ($now->greaterThan($limit)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => ['Só é possível desistir com antecedência mínima de 60 minutos.'],
                ]);
            }
        }

        $isMandante = $user->id === $partida->mandante->user_id;
        $winnerUserId = $isMandante ? $partida->visitante->user_id : $partida->mandante->user_id;

        $payload = [
            'wo_para_user_id' => $winnerUserId,
            'wo_motivo' => 'outro',
            'placar_mandante' => $isMandante ? 0 : 3,
            'placar_visitante' => $isMandante ? 3 : 0,
        ];

        $this->state->transitionTo(
            $partida,
            'wo',
            $payload,
            'wo_declarado',
            $user->id,
            [
                'reason' => 'desistencia',
                'winner_user_id' => $winnerUserId,
                'acionado_por' => $user->id,
            ],
        );

        return response()->json([
            'message' => 'Partida encerrada por W.O.',
            'estado' => $partida->estado,
            'wo_para_user_id' => $partida->wo_para_user_id,
            'wo_motivo' => $partida->wo_motivo,
            'placar_mandante' => $partida->placar_mandante,
            'placar_visitante' => $partida->placar_visitante,
        ]);
    }

    public function denunciar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertParticipante($user->id, $partida);
        $this->state->assertActionAllowed($partida, ['placar_registrado']);

        if ($partida->placar_registrado_por === $user->id) {
            abort(403, 'O registrante nao pode denunciar a partida.');
        }

        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:1000'],
        ]);

        $denuncia = PartidaDenuncia::create([
            'partida_id' => $partida->id,
            'user_id' => $user->id,
            'motivo' => 'texto',
            'descricao' => $data['descricao'],
        ]);

        $this->state->transitionTo(
            $partida,
            'em_reclamacao',
            [],
            'placar_denunciado',
            $user->id,
            ['denuncia_id' => $denuncia->id],
        );

        return response()->json([
            'message' => 'Denúncia registrada.',
            'estado' => $partida->estado,
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
