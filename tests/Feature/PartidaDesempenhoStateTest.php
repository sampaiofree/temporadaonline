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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartidaDesempenhoStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_allows_agendada_state(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('agendada');

        $response = $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [],
                'visitante' => [],
                'placar_mandante' => 2,
                'placar_visitante' => 1,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'estado' => 'placar_registrado',
                'placar_mandante' => 2,
                'placar_visitante' => 1,
            ]);

        $this->assertDatabaseHas('partidas', [
            'id' => $partida->id,
            'estado' => 'placar_registrado',
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'placar_registrado_por' => $mandanteUser->id,
        ]);
    }

    public function test_confirm_rejects_finalized_state(): void
    {
        [$partida, $mandanteUser] = $this->createPartida('finalizada');

        $this
            ->actingAs($mandanteUser)
            ->postJson("/api/partidas/{$partida->id}/desempenho/confirm", [
                'mandante' => [],
                'visitante' => [],
                'placar_mandante' => 1,
                'placar_visitante' => 0,
            ])
            ->assertForbidden();
    }

    /**
     * @return array{0: Partida, 1: User, 2: User}
     */
    private function createPartida(string $estado): array
    {
        $suffix = uniqid('', true);

        $jogo = Jogo::query()->create([
            'nome' => "Jogo {$suffix}",
            'slug' => "jogo-{$suffix}",
        ]);

        $geracao = Geracao::query()->create([
            'nome' => "Geracao {$suffix}",
            'slug' => "geracao-{$suffix}",
        ]);

        $plataforma = Plataforma::query()->create([
            'nome' => "Plataforma {$suffix}",
            'slug' => "plataforma-{$suffix}",
        ]);

        $confederacao = Confederacao::query()->create([
            'nome' => "Conf {$suffix}",
            'timezone' => 'UTC',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::query()->create([
            'nome' => "Liga {$suffix}",
            'descricao' => 'Liga de teste',
            'regras' => 'Regras de teste',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'confederacao_id' => $confederacao->id,
        ]);

        $mandanteUser = User::factory()->create();
        $visitanteUser = User::factory()->create();

        $mandante = LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $mandanteUser->id,
            'nome' => 'Mandante FC',
        ]);

        $visitante = LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $visitanteUser->id,
            'nome' => 'Visitante FC',
        ]);

        $partida = Partida::query()->create([
            'liga_id' => $liga->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => $estado,
        ]);

        return [$partida, $mandanteUser, $visitanteUser];
    }
}
