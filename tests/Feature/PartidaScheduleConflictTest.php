<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\User;
use App\Models\UserDisponibilidade;
use App\Services\PartidaSchedulerService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartidaScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function setupLiga(): array
    {
        $suffix = str_replace('.', '', uniqid('schedule', true));

        $plataforma = Plataforma::create([
            'nome' => "PlayStation {$suffix}",
            'slug' => "ps-{$suffix}",
        ]);

        $jogo = Jogo::create([
            'nome' => "FC {$suffix}",
            'slug' => "fc-{$suffix}",
        ]);

        $geracao = Geracao::create([
            'nome' => "Geracao {$suffix}",
            'slug' => "geracao-{$suffix}",
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Confederacao {$suffix}",
            'descricao' => 'Confederacao de teste para agenda.',
            'timezone' => 'UTC',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::create([
            'nome' => "Liga {$suffix}",
            'descricao' => 'Liga de teste.',
            'regras' => 'Regras.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 8,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 1000000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $mandanteUser = User::factory()->create();
        $visitanteUser = User::factory()->create();
        $mandanteUser->ligas()->attach($liga->id);
        $visitanteUser->ligas()->attach($liga->id);

        $mandante = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $mandanteUser->id,
            'nome' => 'Mandante',
        ]);

        $visitante = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $visitanteUser->id,
            'nome' => 'Visitante',
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            UserDisponibilidade::create([
                'user_id' => $mandanteUser->id,
                'dia_semana' => $day,
                'hora_inicio' => '18:00',
                'hora_fim' => '23:00',
            ]);

            UserDisponibilidade::create([
                'user_id' => $visitanteUser->id,
                'dia_semana' => $day,
                'hora_inicio' => '18:00',
                'hora_fim' => '23:00',
            ]);
        }

        return [$liga, $mandante, $visitante, $mandanteUser, $visitanteUser];
    }

    public function test_candidate_slots_do_not_include_conflicts(): void
    {
        Carbon::setTestNow('2025-01-08 12:00:00');
        [$liga, $mandante, $visitante, $mandanteUser] = $this->setupLiga();

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
        ])->fresh(['liga.confederacao', 'mandante.user', 'visitante.user']);

        $slots = $scheduler->availableOpponentSlots($novaPartida, $mandanteUser->id);
        $conflictSlot = Carbon::parse('2025-01-08 20:00:00', 'UTC')->toIso8601String();

        $this->assertFalse(
            $slots->contains(fn (Carbon $slot) => $slot->toIso8601String() === $conflictSlot),
            'Slots nao devem incluir horario em conflito.',
        );
    }

    public function test_confirm_horario_blocks_conflict(): void
    {
        Carbon::setTestNow('2025-01-08 12:00:00');
        [$liga, $mandante, $visitante, $mandanteUser] = $this->setupLiga();

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'scheduled_at' => Carbon::parse('2025-01-08 20:00:00', 'UTC'),
            'estado' => 'confirmada',
        ]);

        $partida = Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => 'confirmacao_necessaria',
        ]);

        $response = $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/agendar", [
                'datetime' => Carbon::parse('2025-01-08 20:00:00', 'UTC')->toIso8601String(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['datetime']);
    }
}
