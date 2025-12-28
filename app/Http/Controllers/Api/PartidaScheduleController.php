<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partida;
use App\Services\PartidaSchedulerService;
use App\Services\PartidaStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PartidaScheduleController extends Controller
{
    public function __construct(
        private readonly PartidaSchedulerService $scheduler,
        private readonly PartidaStateService $state,
    )
    {
    }

    public function slots(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertVisitante($user->id, $partida);
        $this->assertAgendamentoPermitido($partida);

        $partida->loadMissing('liga');
        $tz = $partida->liga->timezone ?? 'UTC';

        $slots = $this->scheduler->availableVisitorSlots($partida);

        $days = $slots
            ->groupBy(fn (Carbon $slot) => $slot->copy()->setTimezone($tz)->toDateString())
            ->sortKeys()
            ->map(function ($items, $date) use ($tz) {
                $dateLocal = Carbon::parse($date, $tz);

                return [
                    'date' => $date,
                    'label' => $dateLocal->translatedFormat('D, d M'),
                    'slots' => $items->map(function (Carbon $slot) use ($tz) {
                        $slotLocal = $slot->copy()->setTimezone($tz);

                        return [
                            'datetime_utc' => $slot->toIso8601String(),
                            'datetime_local' => $slotLocal->toIso8601String(),
                            'time_label' => $slotLocal->format('H:i'),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'partida_id' => $partida->id,
            'estado' => $partida->estado,
            'timezone' => $tz,
            'days' => $days,
        ]);
    }

    public function agendar(Request $request, Partida $partida): JsonResponse
    {
        $user = $request->user();
        $this->assertVisitante($user->id, $partida);
        $this->assertAgendamentoPermitido($partida);

        $data = $request->validate([
            'datetime' => ['required', 'date'],
        ]);

        $partida->loadMissing('liga');
        $tz = $partida->liga->timezone ?? 'UTC';
        $slot = Carbon::parse($data['datetime'], 'UTC')->setTimezone('UTC')->second(0);
        $validSlots = $this->scheduler->availableVisitorSlots($partida);

        $isValid = $validSlots->contains(fn (Carbon $candidate) => $candidate->equalTo($slot));

        if (! $isValid) {
            throw ValidationException::withMessages([
                'datetime' => ['O horário selecionado não está mais disponível.'],
            ]);
        }

        $this->state->transitionTo(
            $partida,
            'confirmada',
            [
                'scheduled_at' => $slot,
                'forced_by_system' => false,
                'sem_slot_disponivel' => false,
            ],
            'confirmacao_horario',
            $user->id,
            [
                'datetime_local' => $slot->copy()->setTimezone($tz)->toIso8601String(),
                'datetime_utc' => $slot->toIso8601String(),
            ],
        );

        return response()->json([
            'message' => 'Horário confirmado.',
            'estado' => 'confirmada',
            'scheduled_at' => $slot->copy()->setTimezone($tz)->toIso8601String(),
        ]);
    }

    private function assertVisitante(int $userId, Partida $partida): void
    {
        $partida->loadMissing(['mandante', 'visitante']);
        $visitanteUserId = $partida->visitante?->user_id;

        if (! $visitanteUserId || $userId !== $visitanteUserId) {
            abort(403, 'Apenas o visitante pode agendar esta partida.');
        }
    }

    private function assertAgendamentoPermitido(Partida $partida): void
    {
        if ($partida->estado !== 'confirmacao_necessaria' || $partida->scheduled_at) {
            throw ValidationException::withMessages([
                'estado' => ['Esta partida não permite agendamento agora.'],
            ]);
        }
    }
}
