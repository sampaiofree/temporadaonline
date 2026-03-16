<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaRouboMulta;
use App\Models\Plataforma;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyMarketDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'America/Sao_Paulo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_paginated_market_data_returns_ok_and_enables_multa_only_when_window_is_open(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLeagueContext('market-a');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach($liga->id);

        $viewerClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $viewer->id,
            'nome' => 'Meu Clube',
        ]);

        $otherOwner = User::factory()->create();
        $otherClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $otherOwner->id,
            'nome' => 'Clube Adversario',
        ]);

        $player = Elencopadrao::create([
            'jogo_id' => $liga->jogo_id,
            'player_id' => 1001,
            'short_name' => 'J. Teste',
            'long_name' => 'Jogador Teste',
            'player_positions' => 'ST',
            'overall' => 85,
            'value_eur' => 10000000,
            'wage_eur' => 100000,
        ]);

        LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $otherClub->id,
            'elencopadrao_id' => $player->id,
            'value_eur' => 12000000,
            'wage_eur' => 120000,
            'ativo' => true,
        ]);

        LigaRouboMulta::create([
            'confederacao_id' => $confederacao->id,
            'inicio' => '2026-03-16 10:00:00',
            'fim' => '2026-03-16 14:00:00',
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.market.data', [
                'confederacao_id' => $confederacao->id,
                'page' => 1,
                'per_page' => 20,
            ]));

        $response->assertOk();
        $response->assertJsonPath('clube.id', $viewerClub->id);
        $response->assertJsonPath('mercado.multa_enabled', true);
        $response->assertJsonPath('mercado.players.0.elencopadrao_id', $player->id);
        $response->assertJsonPath('mercado.players.0.can_multa', true);
    }

    public function test_non_paginated_market_data_keeps_can_multa_false_when_window_is_closed(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLeagueContext('market-b');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach($liga->id);

        LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $viewer->id,
            'nome' => 'Meu Clube',
        ]);

        $otherOwner = User::factory()->create();
        $otherClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $otherOwner->id,
            'nome' => 'Clube Adversario',
        ]);

        $player = Elencopadrao::create([
            'jogo_id' => $liga->jogo_id,
            'player_id' => 1002,
            'short_name' => 'J. Fechado',
            'long_name' => 'Jogador Fechado',
            'player_positions' => 'CM',
            'overall' => 80,
            'value_eur' => 8000000,
            'wage_eur' => 90000,
        ]);

        LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $otherClub->id,
            'elencopadrao_id' => $player->id,
            'value_eur' => 9000000,
            'wage_eur' => 100000,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.market.data', [
                'confederacao_id' => $confederacao->id,
            ]));

        $response->assertOk();
        $response->assertJsonPath('mercado.multa_enabled', false);
        $response->assertJsonPath('mercado.players.0.elencopadrao_id', $player->id);
        $response->assertJsonPath('mercado.players.0.can_multa', false);
    }

    /**
     * @return array{liga:Liga, confederacao:Confederacao}
     */
    private function createLeagueContext(string $suffix): array
    {
        $unique = Str::slug($suffix).'-'.Str::lower(Str::random(6));

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
            'jogo_id' => $jogo->id,
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Conf {$unique}",
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'ganho_vitoria_partida' => 750000,
            'ganho_empate_partida' => 300000,
            'ganho_derrota_partida' => 50000,
        ]);

        $liga = Liga::create([
            'nome' => "Liga {$unique}",
            'descricao' => "Descricao {$unique}",
            'regras' => "Regras {$unique}",
            'status' => 'ativa',
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'saldo_inicial' => 100000000,
            'multa_multiplicador' => 2,
        ]);

        return [
            'liga' => $liga,
            'confederacao' => $confederacao,
        ];
    }
}
