<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLegacyFirstAccessCompleted;
use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\Plataforma;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyPublicClubProfileFinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
            EnsureLegacyFirstAccessCompleted::class,
        ]);
    }

    public function test_public_profile_returns_finance_data_for_target_rival_club(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao, 'jogo' => $jogo] = $this->createLigaContext([
            'saldo_inicial' => 120_000_000,
        ]);

        $viewer = User::factory()->create();
        $rivalOwner = User::factory()->create();

        $viewer->ligas()->attach($liga->id);
        $rivalOwner->ligas()->attach($liga->id);

        $viewerClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $viewer->id,
            'nome' => 'Clube Viewer',
        ]);

        $rivalClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $rivalOwner->id,
            'nome' => 'Clube Rival',
        ]);

        LigaClubeFinanceiro::create([
            'liga_id' => $liga->id,
            'clube_id' => $viewerClub->id,
            'saldo' => 900_000_000,
        ]);

        LigaClubeFinanceiro::create([
            'liga_id' => $liga->id,
            'clube_id' => $rivalClub->id,
            'saldo' => 210_000_000,
        ]);

        $playerA = $this->createElenco($jogo, ['long_name' => 'Rival Wage A', 'wage_eur' => 50_000_000]);
        $playerB = $this->createElenco($jogo, ['long_name' => 'Rival Wage B', 'wage_eur' => 20_000_000]);

        LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $rivalClub->id,
            'elencopadrao_id' => $playerA->id,
            'value_eur' => 100_000_000,
            'wage_eur' => 50_000_000,
            'ativo' => true,
        ]);

        LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $rivalClub->id,
            'elencopadrao_id' => $playerB->id,
            'value_eur' => 80_000_000,
            'wage_eur' => 20_000_000,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id={$rivalClub->id}");

        $response->assertOk()
            ->assertJsonPath('clube.id', $rivalClub->id)
            ->assertJsonPath('clube.financeiro.saldo', 210_000_000)
            ->assertJsonPath('clube.financeiro.salario_por_rodada', 70_000_000)
            ->assertJsonPath('clube.financeiro.poder_investimento', 140_000_000);

        $this->assertNotSame(
            900_000_000,
            (int) $response->json('clube.financeiro.saldo'),
            'O saldo retornado não pode vir do clube do usuário logado.',
        );
    }

    public function test_public_profile_uses_liga_initial_balance_when_target_wallet_is_missing(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLigaContext([
            'saldo_inicial' => 120_000_000,
        ]);

        $viewer = User::factory()->create();
        $rivalOwner = User::factory()->create();

        $viewer->ligas()->attach($liga->id);
        $rivalOwner->ligas()->attach($liga->id);

        $viewerClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $viewer->id,
            'nome' => 'Clube Viewer',
        ]);

        $rivalClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $rivalOwner->id,
            'nome' => 'Clube Rival',
        ]);

        LigaClubeFinanceiro::create([
            'liga_id' => $liga->id,
            'clube_id' => $viewerClub->id,
            'saldo' => 350_000_000,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id={$rivalClub->id}");

        $response->assertOk()
            ->assertJsonPath('clube.id', $rivalClub->id)
            ->assertJsonPath('clube.financeiro.saldo', 120_000_000)
            ->assertJsonPath('clube.financeiro.salario_por_rodada', 0)
            ->assertJsonPath('clube.financeiro.poder_investimento', 120_000_000);
    }

    /**
     * @return array{liga:Liga, confederacao:Confederacao, jogo:Jogo}
     */
    private function createLigaContext(array $ligaOverrides = []): array
    {
        $unique = Str::lower(Str::random(6));

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
        ]);

        $liga = Liga::create(array_merge([
            'nome' => "Liga {$unique}",
            'descricao' => 'Liga de teste para perfil publico.',
            'regras' => 'Regras de teste.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 20,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 100_000_000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ], $ligaOverrides));

        return [
            'liga' => $liga,
            'confederacao' => $confederacao,
            'jogo' => $jogo,
        ];
    }

    private function createElenco(Jogo $jogo, array $overrides = []): Elencopadrao
    {
        $suffix = Str::lower(Str::random(8));

        return Elencopadrao::create(array_merge([
            'jogo_id' => $jogo->id,
            'player_id' => "player-{$suffix}",
            'short_name' => "P{$suffix}",
            'long_name' => "Player {$suffix}",
            'player_positions' => 'CM',
            'overall' => 80,
            'value_eur' => 0,
            'wage_eur' => 0,
        ], $overrides));
    }
}

