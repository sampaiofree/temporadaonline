<?php

namespace Tests\Feature;

use App\Models\ClubeTamanho;
use App\Models\Confederacao;
use App\Models\Conquista;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeConquista;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyMyClubDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_my_club_data_uses_clube_tamanho_records_from_database(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLeagueContext('club-size');

        ClubeTamanho::insert([
            [
                'nome' => 'LOCAL',
                'descricao' => 'Clube local',
                'n_fans' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'REGIONAL',
                'descricao' => 'Clube regional',
                'n_fans' => 750000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'NACIONAL',
                'descricao' => 'Clube nacional',
                'n_fans' => 1500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        $club = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Clube Teste',
        ]);

        $conquista = Conquista::create([
            'nome' => 'Meta de torcida',
            'descricao' => 'Gera torcida suficiente para mudar de nivel',
            'imagem' => 'conquistas/meta-torcida.png',
            'tipo' => 'gols',
            'quantidade' => 1,
            'fans' => 1200000,
        ]);

        LigaClubeConquista::create([
            'liga_id' => $liga->id,
            'liga_clube_id' => $club->id,
            'user_id' => $user->id,
            'confederacao_id' => $confederacao->id,
            'conquista_id' => $conquista->id,
            'claimed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('legacy.my_club.data', [
                'confederacao_id' => $confederacao->id,
            ]));

        $response->assertOk();
        $response->assertJsonPath('clube.fans', 1200000);
        $response->assertJsonPath('clube.club_size_name', 'REGIONAL');
        $response->assertJsonPath('clube.club_size_tiers.0.name', 'LOCAL');
        $response->assertJsonPath('clube.club_size_tiers.1.name', 'REGIONAL');
        $response->assertJsonPath('clube.club_size_tiers.1.min_fans', 750000);
        $response->assertJsonPath('clube.club_size_tiers.2.name', 'NACIONAL');
        $response->assertJsonPath('clube.club_size_tiers.2.min_fans', 1500000);
    }

    public function test_my_club_data_logs_when_tournaments_source_has_no_league(): void
    {
        ['confederacao' => $confederacao] = $this->createLeagueContext('tournaments-no-league');

        $user = User::factory()->create();

        Log::spy();

        $response = $this
            ->actingAs($user)
            ->get(route('legacy.my_club.data', [
                'confederacao_id' => $confederacao->id,
                'legacy_source' => 'tournaments',
            ]));

        $response->assertNotFound();
        $response->assertJsonPath('message', 'Nenhuma liga encontrada para esta confederacao.');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context = []): bool {
                return $message === 'Legacy tournaments: no league found for selected confederacao.';
            });
    }

    public function test_my_club_data_logs_when_tournaments_source_has_league_but_no_club(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLeagueContext('tournaments-no-club');

        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        Log::spy();

        $response = $this
            ->actingAs($user)
            ->get(route('legacy.my_club.data', [
                'confederacao_id' => $confederacao->id,
                'legacy_source' => 'tournaments',
            ]));

        $response->assertOk();
        $response->assertJsonPath('liga.id', $liga->id);
        $response->assertJsonPath('clube', null);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context = []): bool {
                return $message === 'Legacy tournaments: user is in league but has no club for selected confederacao.'
                    && array_key_exists('liga_id', $context)
                    && array_key_exists('liga_nome', $context);
            });
    }

    public function test_my_club_data_skill_rating_includes_placar_registrado_matches(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao] = $this->createLeagueContext('skill-rating');

        $user = User::factory()->create();
        $opponent = User::factory()->create();

        $user->ligas()->attach($liga->id);
        $opponent->ligas()->attach($liga->id);

        $club = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Clube Teste',
        ]);

        $opponentClub = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $opponent->id,
            'nome' => 'Clube Oponente',
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $club->id,
            'visitante_id' => $opponentClub->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'placar_registrado_por' => $user->id,
            'placar_registrado_em' => now()->subDay(),
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $opponentClub->id,
            'visitante_id' => $club->id,
            'estado' => 'placar_registrado',
            'placar_mandante' => 3,
            'placar_visitante' => 0,
            'placar_registrado_por' => $opponent->id,
            'placar_registrado_em' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('legacy.my_club.data', [
                'confederacao_id' => $confederacao->id,
            ]));

        $response->assertOk();
        $response->assertJsonPath('clube.id', $club->id);
        $response->assertJsonPath('clube.wins', 1);
        $response->assertJsonPath('clube.skill_rating', 50);
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
