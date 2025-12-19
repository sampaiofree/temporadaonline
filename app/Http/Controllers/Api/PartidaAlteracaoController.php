<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Models\PartidaConfirmacao;
use App\Models\PartidaOpcaoHorario;
use App\Services\PartidaSchedulerService;
use App\Services\PartidaStateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartidaAlteracaoController extends Controller
{
    public function __construct(
        private readonly PartidaStateService $state,
        private readonly PartidaSchedulerService $scheduler,
    ) {
    }

    public function alterar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $partida->loadMissing(['liga', 'mandante.user', 'visitante.user']);

        // Apenas mandante pode alterar
        if ($partida->mandante->user_id !== $user->id) {
            abort(403, 'Apenas o mandante pode alterar o horário.');
        }

        // Estados permitidos para alteração
        $this->state->assertActionAllowed($partida, ['confirmacao_necessaria', 'agendada', 'confirmada']);

        $data = $request->validate([
            'datetime' => ['required', 'date'],
        ]);

        $liga = $partida->liga;
        $tz = $liga->timezone ?? 'UTC';
        $novoHorarioLocal = Carbon::parse($data['datetime'], $tz);
        $novoHorarioUtc = $novoHorarioLocal->copy()->setTimezone('UTC');

        $this->assertAntecedencia($novoHorarioUtc, (int) ($liga->antecedencia_minima_alteracao_horas ?? 10));
        $this->assertAlteracoesRestantes($partida, (int) ($liga->max_alteracoes_horario ?? 1));
        $this->assertDentroRegrasLiga($liga, $novoHorarioLocal);
        $this->assertDisponibilidadeUsuarios($partida, $novoHorarioLocal);
        $this->assertSemConflito($partida, $novoHorarioUtc);

        // resetar confirmações/opções e aplicar novo agendamento
        PartidaConfirmacao::where('partida_id', $partida->id)->delete();
        PartidaOpcaoHorario::where('partida_id', $partida->id)->delete();

        $partida->alteracoes_usadas = (int) $partida->alteracoes_usadas + 1;
        $partida->forced_by_system = false;
        $partida->sem_slot_disponivel = false;

        $this->state->transitionTo(
            $partida,
            'agendada',
            ['scheduled_at' => $novoHorarioUtc],
            'alteracao_horario',
            $user->id,
            [
                'novo_horario_local' => $novoHorarioLocal->toIso8601String(),
                'novo_horario_utc' => $novoHorarioUtc->toIso8601String(),
            ],
        );

        return response()->json([
            'message' => 'Horário atualizado.',
            'scheduled_at' => $novoHorarioUtc->toIso8601String(),
        ]);
    }

    private function assertAntecedencia(Carbon $novoHorarioUtc, int $minHoras): void
    {
        $limite = Carbon::now('UTC')->addHours($minHoras);

        if ($novoHorarioUtc->lessThan($limite)) {
            throw ValidationException::withMessages([
                'datetime' => ["Precisa estar com antecedência mínima de {$minHoras}h."],
            ]);
        }
    }

    private function assertAlteracoesRestantes(Partida $partida, int $max): void
    {
        if ((int) $partida->alteracoes_usadas >= $max) {
            throw ValidationException::withMessages([
                'alteracoes' => ['Limite de alterações atingido.'],
            ]);
        }
    }

    private function assertDentroRegrasLiga($liga, Carbon $novoHorarioLocal): void
    {
        $day = $novoHorarioLocal->dayOfWeek;
        $allowedDays = $this->scheduler->normalizeDays($liga->dias_permitidos ?? []);
        if (! in_array($day, $allowedDays, true)) {
            throw ValidationException::withMessages([
                'datetime' => ['Dia não permitido pela liga.'],
            ]);
        }

        $time = $novoHorarioLocal->format('H:i');
        $ranges = $this->scheduler->normalizeRanges($liga->horarios_permitidos ?? []);
        $inRange = collect($ranges)->contains(function ($range) use ($time) {
            return $time >= $range['start'] && $time <= $range['end'];
        });

        if (! $inRange) {
            throw ValidationException::withMessages([
                'datetime' => ['Horário fora da faixa permitida pela liga.'],
            ]);
        }
    }

    private function assertDisponibilidadeUsuarios(Partida $partida, Carbon $novoHorarioLocal): void
    {
        $visitorUser = $partida->visitante->user;
        $mandanteUser = $partida->mandante->user;
        $day = $novoHorarioLocal->dayOfWeek;
        $time = $novoHorarioLocal->format('H:i');

        $mandanteRanges = $this->scheduler->groupDisponibilidades($mandanteUser)[$day] ?? [];
        $visitanteRanges = $this->scheduler->groupDisponibilidades($visitorUser)[$day] ?? [];

        $inMandante = collect($mandanteRanges)->contains(fn ($r) => $time >= $r['start'] && $time <= $r['end']);
        $inVisitante = collect($visitanteRanges)->contains(fn ($r) => $time >= $r['start'] && $time <= $r['end']);

        if (! $inMandante || ! $inVisitante) {
            throw ValidationException::withMessages([
                'datetime' => ['Horário não está na disponibilidade de ambos os clubes.'],
            ]);
        }
    }

    private function assertSemConflito(Partida $partida, Carbon $novoHorarioUtc): void
    {
        if (
            $this->scheduler->hasScheduleConflict($partida->mandante_id, $novoHorarioUtc) ||
            $this->scheduler->hasScheduleConflict($partida->visitante_id, $novoHorarioUtc)
        ) {
            throw ValidationException::withMessages([
                'datetime' => ['Um dos clubes já possui partida nesse horário.'],
            ]);
        }
    }
}
