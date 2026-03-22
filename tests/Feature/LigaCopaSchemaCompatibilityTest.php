<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaCopaFase;
use App\Models\LigaCopaGrupo;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\LigaCopaService;
use App\Services\PartidaSchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class LigaCopaSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_liga_and_liga_clube_creation_skip_cup_orchestration_when_schema_not_ready(): void
    {
        $context = $this->createCompetitionContext('schema-guard');
        $user = User::factory()->create();

        $this->mock(LigaCopaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('schemaReady')->twice()->andReturnFalse();
            $mock->shouldNotReceive('ensureSetupForLiga');
            $mock->shouldNotReceive('handleClubCreated');
        });

        $this->mock(PartidaSchedulerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('ensureMatchesForClub')->once();
        });

        $liga = Liga::create([
            'nome' => 'Liga Schema Guard',
            'descricao' => 'Teste schema guard',
            'regras' => 'Teste schema guard',
            'status' => 'ativa',
            'confederacao_id' => $context['confederacao']->id,
            'jogo_id' => $context['jogo']->id,
            'geracao_id' => $context['geracao']->id,
            'plataforma_id' => $context['plataforma']->id,
            'saldo_inicial' => 100000000,
            'multa_multiplicador' => 2,
            'max_times' => 8,
        ]);

        LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $context['confederacao']->id,
            'user_id' => $user->id,
            'nome' => 'Clube Schema Guard',
        ]);

        $this->assertDatabaseCount('liga_copa_grupos', 0);
        $this->assertDatabaseCount('liga_copa_fases', 0);
    }

    public function test_liga_copa_route_returns_503_when_schema_is_not_ready(): void
    {
        $context = $this->createLeagueContext('web-503');

        $this->mock(LigaCopaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('schemaReady')->andReturnFalse();
        });

        $response = $this
            ->actingAs($context['user'])
            ->get(route('liga.copa', ['liga_id' => $context['liga']->id]));

        $response->assertStatus(503);
    }

    public function test_legacy_cup_data_returns_503_when_schema_is_not_ready(): void
    {
        $context = $this->createLeagueContext('legacy-503');

        $this->mock(LigaCopaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('schemaReady')->andReturnFalse();
        });

        $response = $this
            ->actingAs($context['user'])
            ->getJson(route('legacy.cup.data', [
                'confederacao_id' => $context['confederacao']->id,
            ]));

        $response->assertStatus(503);
        $response->assertJsonPath('message', 'Copa da Liga indisponivel durante atualizacao.');
    }

    public function test_liga_partidas_route_keeps_working_with_league_fallback_when_cup_schema_is_not_ready(): void
    {
        $context = $this->createLeagueContext('liga-partidas-fallback');

        Partida::create([
            'liga_id' => $context['liga']->id,
            'mandante_id' => $context['clube']->id,
            'visitante_id' => $context['opponent']->id,
            'competition_type' => Partida::COMPETITION_CUP,
            'estado' => 'confirmada',
        ]);

        $this->bindUnavailableCupServiceWithLeagueFallback();

        $response = $this
            ->actingAs($context['user'])
            ->get(route('liga.partidas', ['liga_id' => $context['liga']->id]));

        $response->assertOk();
        $response->assertViewHas('partidas', function (array $partidas): bool {
            return count($partidas) === 1
                && ($partidas[0]['competition_type'] ?? null) === Partida::COMPETITION_LEAGUE
                && ($partidas[0]['competition_label'] ?? null) === 'Liga';
        });
    }

    public function test_legacy_match_center_route_keeps_working_with_league_fallback_when_cup_schema_is_not_ready(): void
    {
        $context = $this->createLeagueContext('legacy-match-center-fallback');

        Partida::create([
            'liga_id' => $context['liga']->id,
            'mandante_id' => $context['clube']->id,
            'visitante_id' => $context['opponent']->id,
            'competition_type' => Partida::COMPETITION_CUP,
            'estado' => 'confirmada',
        ]);

        $this->bindUnavailableCupServiceWithLeagueFallback();

        $response = $this
            ->actingAs($context['user'])
            ->getJson(route('legacy.match_center.data', [
                'confederacao_id' => $context['confederacao']->id,
            ]));

        $response->assertOk();
        $response->assertJsonPath('partidas.0.competition_type', Partida::COMPETITION_LEAGUE);
        $response->assertJsonPath('partidas.0.competition_label', 'Liga');
        $response->assertJsonPath('partidas.0.cup_phase_label', null);
        $response->assertJsonPath('partidas.0.cup_group_label', null);
    }

    /**
     * @return array{plataforma:Plataforma,jogo:Jogo,geracao:Geracao,confederacao:Confederacao}
     */
    private function createCompetitionContext(string $suffix): array
    {
        $unique = str_replace('.', '', uniqid($suffix, true));

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

        return [
            'plataforma' => $plataforma,
            'jogo' => $jogo,
            'geracao' => $geracao,
            'confederacao' => $confederacao,
        ];
    }

    /**
     * @return array{liga:Liga,user:User,clube:LigaClube,opponent:LigaClube,confederacao:Confederacao}
     */
    private function createLeagueContext(string $suffix): array
    {
        $context = $this->createCompetitionContext($suffix);

        $liga = Liga::create([
            'nome' => "Liga {$suffix}",
            'descricao' => "Descricao {$suffix}",
            'regras' => "Regras {$suffix}",
            'status' => 'ativa',
            'confederacao_id' => $context['confederacao']->id,
            'jogo_id' => $context['jogo']->id,
            'geracao_id' => $context['geracao']->id,
            'plataforma_id' => $context['plataforma']->id,
            'saldo_inicial' => 100000000,
            'multa_multiplicador' => 2,
            'max_times' => 8,
        ]);

        $user = User::factory()->create();
        $opponentUser = User::factory()->create();

        $user->ligas()->attach($liga->id);
        $opponentUser->ligas()->attach($liga->id);

        [$clube, $opponent] = LigaClube::withoutEvents(function () use ($liga, $context, $user, $opponentUser): array {
            return [
                LigaClube::create([
                    'liga_id' => $liga->id,
                    'confederacao_id' => $context['confederacao']->id,
                    'user_id' => $user->id,
                    'nome' => 'Clube Usuario',
                ]),
                LigaClube::create([
                    'liga_id' => $liga->id,
                    'confederacao_id' => $context['confederacao']->id,
                    'user_id' => $opponentUser->id,
                    'nome' => 'Clube Rival',
                ]),
            ];
        });

        return [
            'liga' => $liga,
            'user' => $user,
            'clube' => $clube,
            'opponent' => $opponent,
            'confederacao' => $context['confederacao'],
        ];
    }

    private function bindUnavailableCupServiceWithLeagueFallback(): void
    {
        $this->mock(LigaCopaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('schemaReady')->andReturnFalse();
            $mock->shouldReceive('resolvePartidaCompetitionContext')->andReturn([
                'competition_type' => Partida::COMPETITION_LEAGUE,
                'competition_label' => 'Liga',
                'cup_phase_label' => null,
                'cup_group_label' => null,
            ]);
        });
    }
}
