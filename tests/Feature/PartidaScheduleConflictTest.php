<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\User;
use App\Models\UserDisponibilidade;
use App\Services\PartidaSchedulerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartidaScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private function setupLiga(): array
    {
        $liga = Liga::factory()->create([
            'dias_permitidos' => [1, 2, 3, 4, 5],
            'horarios_permitidos' => [
                ['inicio' => '18:00', 'fim' => '23:00'],
            ],
            'timezone' => 'UTC',
        ]);

        $mandanteUser = User::factory()->create();
        $visitanteUser = User::factory()->create();

        $mandante = LigaClube::factory()->create([
            'liga_id' => $liga->id,
            'user_id' => $mandanteUser->id,
        ]);

        $visitante = LigaClube::factory()->create([
            'liga_id' => $liga->id,
            'user_id' => $visitanteUser->id,
        ]);

        // Disponibilidade total em dias úteis das 18h às 23h
        foreach ([1, 2, 3, 4, 5] as $day) {
            UserDisponibilidade::factory()->create([
                'user_id' => $mandanteUser->id,
                'dia_semana' => $day,
                'hora_inicio' => '18:00',
                'hora_fim' => '23:00',
            ]);

            UserDisponibilidade::factory()->create([
                'user_id' => $visitanteUser->id,
                'dia_semana' => $day,
                'hora_inicio' => '18:00',
                'hora_fim' => '23:00',
            ]);
        }

        return [$liga, $mandante, $visitante];
    }

    public function test_candidate_slots_do_not_include_conflicts(): void
    {
        Carbon::setTestNow('2025-01-08 12:00:00'); // Wednesday
        [$liga, $mandante, $visitante] = $this->setupLiga();

        // Partida já marcada para quarta às 20h
        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'scheduled_at' => Carbon::parse('2025-01-08 20:00:00', 'UTC'),
            'estado' => 'confirmada',
        ]);

        $scheduler = app(PartidaSchedulerService::class);
        $novaPartida = Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => 'confirmacao_necessaria',
        ])->fresh(['liga', 'mandante.user', 'visitante.user']);

        $slots = $scheduler->candidateSlots($novaPartida, 5);

        $conflictSlot = Carbon::parse('2025-01-08 20:00:00', 'UTC')->toIso8601String();
        $this->assertFalse(
            $slots->contains(fn ($slot) => $slot->toIso8601String() === $conflictSlot),
            'Slots não devem incluir horário em conflito'
        );
    }

    public function test_confirm_horario_blocks_conflict(): void
    {
        Carbon::setTestNow('2025-01-08 12:00:00'); // Wednesday
        [$liga, $mandante, $visitante] = $this->setupLiga();

        // Partida já marcada para quarta às 20h
        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'scheduled_at' => Carbon::parse('2025-01-08 20:00:00', 'UTC'),
            'estado' => 'confirmada',
        ]);

        $scheduler = app(PartidaSchedulerService::class);
        $partida = Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => 'confirmacao_necessaria',
        ])->fresh(['liga', 'mandante.user', 'visitante.user']);

        $this->expectException(ValidationException::class);
        $scheduler->confirmHorarios(
            $partida,
            $mandante->user,
            collect([Carbon::parse('2025-01-08 20:00:00', 'UTC')])
        );
    }
}
