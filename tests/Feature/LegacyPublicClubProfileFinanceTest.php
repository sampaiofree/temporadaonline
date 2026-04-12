<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLegacyFirstAccessCompleted;
use App\Models\ClubeTamanho;
use App\Models\Confederacao;
use App\Models\Conquista;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeConquista;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\Partida;
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

    public function test_public_profile_uses_club_name_when_club_id_is_zero(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLigaContext();

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

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id=0&club_name=Clube%20Rival");

        $response->assertOk()
            ->assertJsonPath('clube.id', $rivalClub->id)
            ->assertJsonPath('clube.nome', 'Clube Rival');

        $this->assertNotSame(
            $viewerClub->id,
            (int) $response->json('clube.id'),
            'club_id=0 com club_name preenchido deve resolver o clube informado pelo nome.',
        );
    }

    public function test_public_profile_returns_422_for_explicit_invalid_club_id_without_name(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLigaContext();

        $viewer = User::factory()->create();
        $viewer->ligas()->attach($liga->id);

        LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $viewer->id,
            'nome' => 'Clube Viewer',
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id=0");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'club_id invalido para esta consulta.')
            ->assertJsonPath('clube', null);
    }

    public function test_public_profile_returns_logged_user_club_when_no_selector_is_provided(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLigaContext();

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

        LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $rivalOwner->id,
            'nome' => 'Clube Rival',
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}");

        $response->assertOk()
            ->assertJsonPath('clube.id', $viewerClub->id)
            ->assertJsonPath('clube.nome', 'Clube Viewer');
    }

    public function test_public_profile_skill_rating_includes_placar_registrado_matches(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLigaContext();

        $viewer = User::factory()->create();
        $rivalOwner = User::factory()->create();
        $opponentOwner = User::factory()->create();

        $viewer->ligas()->attach($liga->id);
        $rivalOwner->ligas()->attach($liga->id);
        $opponentOwner->ligas()->attach($liga->id);

        $rivalClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $rivalOwner->id,
            'nome' => 'Clube Rival',
        ]);

        $opponentClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $opponentOwner->id,
            'nome' => 'Clube Oponente',
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $rivalClub->id,
            'visitante_id' => $opponentClub->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'placar_registrado_por' => $rivalOwner->id,
            'placar_registrado_em' => now()->subDay(),
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $opponentClub->id,
            'visitante_id' => $rivalClub->id,
            'estado' => 'placar_registrado',
            'placar_mandante' => 3,
            'placar_visitante' => 0,
            'placar_registrado_por' => $opponentOwner->id,
            'placar_registrado_em' => now(),
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id={$rivalClub->id}");

        $response->assertOk()
            ->assertJsonPath('clube.id', $rivalClub->id)
            ->assertJsonPath('clube.wins', 1)
            ->assertJsonPath('clube.skill_rating', 50);
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

    public function test_public_profile_returns_detailed_player_attributes_for_target_club(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao, 'jogo' => $jogo] = $this->createLigaContext();

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

        $viewerPlayer = $this->createElenco($jogo, [
            'short_name' => 'Viewer Stat',
            'long_name' => 'Viewer Stat Full',
            'player_positions' => 'CM',
            'overall' => 74,
            'pace' => 41,
            'shooting' => 42,
            'passing' => 43,
            'dribbling' => 44,
            'defending' => 45,
            'physic' => 46,
        ]);

        $rivalPlayer = $this->createElenco($jogo, [
            'short_name' => 'Rival Star',
            'long_name' => 'Rival Star Full',
            'player_positions' => 'ST,CF',
            'overall' => 89,
            'value_eur' => 111_000_000,
            'wage_eur' => 7_000_000,
            'player_face_url' => 'https://example.com/rival-star.png',
            'age' => 28,
            'weak_foot' => 4,
            'skill_moves' => 5,
            'player_traits' => 'Rapid,Power Shot',
            'pace' => 91,
            'shooting' => 88,
            'passing' => 84,
            'dribbling' => 87,
            'defending' => 39,
            'physic' => 79,
            'movement_acceleration' => 93,
            'movement_sprint_speed' => 90,
            'movement_agility' => 86,
            'movement_balance' => 80,
            'movement_reactions' => 88,
            'attacking_finishing' => 92,
            'attacking_short_passing' => 85,
            'power_shot_power' => 89,
            'power_long_shots' => 83,
            'power_jumping' => 78,
            'power_stamina' => 82,
            'power_strength' => 81,
            'skill_long_passing' => 77,
            'skill_dribbling' => 90,
            'skill_ball_control' => 88,
            'mentality_vision' => 84,
            'mentality_interceptions' => 35,
            'mentality_aggression' => 67,
            'defending_marking_awareness' => 33,
            'defending_standing_tackle' => 37,
            'defending_sliding_tackle' => 31,
        ]);

        LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $viewerClub->id,
            'elencopadrao_id' => $viewerPlayer->id,
            'value_eur' => 22_000_000,
            'wage_eur' => 1_500_000,
            'ativo' => true,
        ]);

        LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $rivalClub->id,
            'elencopadrao_id' => $rivalPlayer->id,
            'value_eur' => 120_000_000,
            'wage_eur' => 8_000_000,
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id={$rivalClub->id}");

        $response->assertOk()
            ->assertJsonPath('clube.id', $rivalClub->id)
            ->assertJsonPath('clube.players.0.nome', 'Rival Star')
            ->assertJsonPath('clube.players.0.player_face_url', 'https://example.com/rival-star.png')
            ->assertJsonPath('clube.players.0.age', 28)
            ->assertJsonPath('clube.players.0.weak_foot', 4)
            ->assertJsonPath('clube.players.0.skill_moves', 5)
            ->assertJsonPath('clube.players.0.pace', 91)
            ->assertJsonPath('clube.players.0.shooting', 88)
            ->assertJsonPath('clube.players.0.passing', 84)
            ->assertJsonPath('clube.players.0.dribbling', 87)
            ->assertJsonPath('clube.players.0.defending', 39)
            ->assertJsonPath('clube.players.0.physic', 79)
            ->assertJsonPath('clube.players.0.player_traits', 'Rapid,Power Shot')
            ->assertJsonPath('clube.players.0.playstyle_badges.0.name', 'Rapid')
            ->assertJsonPath('clube.players.0.playstyle_badges.1.name', 'Power Shot');

        $this->assertNotSame(
            41,
            (int) $response->json('clube.players.0.pace'),
            'Os atributos retornados não podem vir do elenco do usuário logado.',
        );
    }

    public function test_public_profile_returns_club_size_tiers_from_database(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLigaContext();

        $viewer = User::factory()->create();
        $rivalOwner = User::factory()->create();

        $viewer->ligas()->attach($liga->id);
        $rivalOwner->ligas()->attach($liga->id);

        $rivalClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $rivalOwner->id,
            'nome' => 'Clube Rival',
        ]);

        ClubeTamanho::insert([
            [
                'nome' => 'LOCAL',
                'descricao' => 'Local',
                'n_fans' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'REGIONAL',
                'descricao' => 'Regional',
                'n_fans' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'NACIONAL',
                'descricao' => 'Nacional',
                'n_fans' => 1500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $conquista = Conquista::create([
            'nome' => 'Torcida rival',
            'descricao' => 'Aumenta torcida',
            'imagem' => 'conquistas/torcida-rival.png',
            'tipo' => 'gols',
            'quantidade' => 1,
            'fans' => 800000,
        ]);

        LigaClubeConquista::create([
            'liga_id' => $liga->id,
            'liga_clube_id' => $rivalClub->id,
            'user_id' => $rivalOwner->id,
            'confederacao_id' => $confederacao->id,
            'conquista_id' => $conquista->id,
            'claimed_at' => now(),
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson("/legacy/public-club-profile-data?confederacao_id={$confederacao->id}&club_id={$rivalClub->id}");

        $response->assertOk()
            ->assertJsonPath('clube.club_size_name', 'REGIONAL')
            ->assertJsonPath('clube.club_size_tiers.0.name', 'LOCAL')
            ->assertJsonPath('clube.club_size_tiers.1.name', 'REGIONAL')
            ->assertJsonPath('clube.club_size_tiers.1.min_fans', 500000)
            ->assertJsonPath('clube.club_size_tiers.2.name', 'NACIONAL');
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
            'max_times' => 16,
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
