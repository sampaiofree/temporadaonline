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
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyLeagueTableDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_league_table_endpoint_returns_draws_count_for_each_club(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLeagueContext('LT');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach([$liga->id]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        $clubA = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $userA->id,
            'nome' => 'Clube A',
        ]);

        $clubB = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $userB->id,
            'nome' => 'Clube B',
        ]);

        $clubC = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $userC->id,
            'nome' => 'Clube C',
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $clubA->id,
            'visitante_id' => $clubB->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 1,
            'placar_visitante' => 1,
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $clubA->id,
            'visitante_id' => $clubC->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 2,
            'placar_visitante' => 0,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.league_table.data', [
                'confederacao_id' => $confederacao->id,
            ]));

        $response->assertOk();

        $rows = collect($response->json('table.rows'))->keyBy('club_name');

        $this->assertSame(1, $rows->get('Clube A')['draws'] ?? null);
        $this->assertSame(1, $rows->get('Clube B')['draws'] ?? null);
        $this->assertSame(0, $rows->get('Clube C')['draws'] ?? null);
    }

    /**
     * @return array{liga:Liga, confederacao:Confederacao}
     */
    private function createLeagueContext(string $suffix): array
    {
        $unique = Str::lower(Str::random(6)).Str::lower($suffix);

        $plataforma = Plataforma::create([
            'nome' => "Plataforma {$unique}",
            'slug' => "plat-{$unique}",
        ]);

        $jogo = Jogo::create([
            'nome' => "Jogo {$unique}",
            'slug' => "jogo-{$unique}",
        ]);

        $geracao = Geracao::create([
            'nome' => "Geracao {$unique}",
            'slug' => "geracao-{$unique}",
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Confederacao {$unique}",
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'timezone' => 'America/Sao_Paulo',
        ]);

        $liga = Liga::create([
            'nome' => "Liga {$unique}",
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

        return [
            'liga' => $liga,
            'confederacao' => $confederacao,
        ];
    }
}
