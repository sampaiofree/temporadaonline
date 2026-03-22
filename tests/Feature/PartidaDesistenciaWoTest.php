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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartidaDesistenciaWoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_desistir_allows_confirmada_ten_minutes_before_match(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'UTC'));

        [$partida, $mandanteUser, $visitante] = $this->createPartida('confirmada', now('UTC')->addMinutes(10));

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desistir")
            ->assertOk()
            ->assertJson([
                'message' => 'Partida encerrada por W.O.',
                'estado' => 'wo',
                'wo_para_user_id' => $visitante->user_id,
                'wo_motivo' => 'outro',
                'placar_mandante' => 0,
                'placar_visitante' => 3,
            ]);

        $this->assertDatabaseHas('partidas', [
            'id' => $partida->id,
            'estado' => 'wo',
            'wo_para_user_id' => $visitante->user_id,
            'wo_motivo' => 'outro',
            'placar_mandante' => 0,
            'placar_visitante' => 3,
        ]);

        $this->assertDatabaseHas('partida_eventos', [
            'partida_id' => $partida->id,
            'tipo' => 'wo_declarado',
            'user_id' => $mandanteUser->id,
        ]);
    }

    public function test_desistir_allows_confirmada_two_hours_before_match(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'UTC'));

        [$partida, $mandanteUser, $visitante] = $this->createPartida('confirmada', now('UTC')->addHours(2));

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desistir")
            ->assertOk()
            ->assertJson([
                'estado' => 'wo',
                'wo_para_user_id' => $visitante->user_id,
                'placar_mandante' => 0,
                'placar_visitante' => 3,
            ]);
    }

    public function test_desistir_rejects_confirmada_match_at_start_time(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'UTC'));

        [$partida, $mandanteUser] = $this->createPartida('confirmada', now('UTC'));

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desistir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseHas('partidas', [
            'id' => $partida->id,
            'estado' => 'confirmada',
        ]);
    }

    public function test_desistir_rejects_agendada_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'UTC'));

        [$partida, $mandanteUser] = $this->createPartida('agendada', now('UTC')->addHours(2));

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desistir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado');
    }

    public function test_desistir_rejects_confirmacao_necessaria_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'UTC'));

        [$partida, $mandanteUser] = $this->createPartida('confirmacao_necessaria', now('UTC')->addHours(2));

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desistir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado');
    }

    /**
     * @return array{0: Partida, 1: User, 2: LigaClube}
     */
    private function createPartida(string $estado, Carbon $scheduledAt): array
    {
        $suffix = Str::lower(Str::random(6));

        $plataforma = Plataforma::create([
            'nome' => "Plataforma {$suffix}",
            'slug' => "plat-{$suffix}",
        ]);

        $jogo = Jogo::create([
            'nome' => "Jogo {$suffix}",
            'slug' => "jogo-{$suffix}",
        ]);

        $geracao = Geracao::create([
            'nome' => "Geracao {$suffix}",
            'slug' => "geracao-{$suffix}",
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Confederacao {$suffix}",
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'timezone' => 'UTC',
            'ganho_vitoria_partida' => 750000,
            'ganho_empate_partida' => 300000,
            'ganho_derrota_partida' => 50000,
        ]);

        $liga = Liga::create([
            'nome' => "Liga {$suffix}",
            'descricao' => 'Liga de teste',
            'regras' => 'Regras de teste',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
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
            'confederacao_id' => $confederacao->id,
            'user_id' => $mandanteUser->id,
            'nome' => "Mandante {$suffix}",
        ]);

        $visitante = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $visitanteUser->id,
            'nome' => "Visitante {$suffix}",
        ]);

        $partida = Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => $estado,
            'scheduled_at' => $scheduledAt->copy()->utc(),
        ]);

        return [$partida, $mandanteUser, $visitante];
    }
}
