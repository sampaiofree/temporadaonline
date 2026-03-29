<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaCopaFase;
use App\Models\LigaCopaGrupo;
use App\Models\LigaCopaGrupoClube;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\PartidaSchedulerService;
use App\Services\LigaClassificacaoService;
use App\Services\LigaCopaService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PDOException;
use Tests\TestCase;

class LigaCopaMvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_rejects_invalid_max_times_for_cup_mvp(): void
    {
        $context = $this->createCompetitionContext();
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->from('/admin/ligas/create')
            ->post('/admin/ligas', [
                'nome' => 'Liga Invalida',
                'confederacao_id' => $context['confederacao']->id,
                'max_times' => 24,
                'saldo_inicial' => 1000000,
                'status' => 'ativa',
                'descricao' => 'Teste',
                'regras' => 'Teste',
            ]);

        $response->assertRedirect('/admin/ligas/create');
        $response->assertSessionHasErrors(['max_times']);
        $this->assertDatabaseMissing('ligas', ['nome' => 'Liga Invalida']);
    }

    public function test_liga_creation_precreates_cup_groups_from_max_times(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 16]);

        $groups = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->orderBy('ordem')
            ->get();

        $this->assertCount(4, $groups);
        $this->assertSame(['Grupo A', 'Grupo B', 'Grupo C', 'Grupo D'], $groups->pluck('label')->all());

        $this->assertDatabaseHas('liga_copa_fases', [
            'liga_id' => $liga->id,
            'tipo' => LigaCopaService::PHASE_GROUPS,
            'status' => LigaCopaService::STATUS_ACTIVE,
        ]);
    }

    public function test_fourth_club_closes_group_and_generates_twelve_cup_matches_once(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 8]);

        $clubs = [];

        for ($index = 1; $index <= 4; $index++) {
            $clubs[] = $this->createClub($liga, "Clube {$index}");
        }

        $grupoA = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->where('ordem', 1)
            ->firstOrFail();

        $this->assertSame(4, $grupoA->memberships()->count());
        $this->assertSame(
            12,
            Partida::query()
                ->cupCompetition()
                ->where('liga_id', $liga->id)
                ->whereHas('cupMeta', fn ($query) => $query->where('grupo_id', $grupoA->id))
                ->count(),
        );

        app(LigaCopaService::class)->handleClubCreated($clubs[3]->fresh());

        $this->assertSame(
            12,
            Partida::query()
                ->cupCompetition()
                ->where('liga_id', $liga->id)
                ->whereHas('cupMeta', fn ($query) => $query->where('grupo_id', $grupoA->id))
                ->count(),
        );
    }

    public function test_knockout_is_generated_only_after_all_groups_finish_with_fixed_adjacent_bracket(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 8]);

        $clubs = [];

        for ($index = 1; $index <= 8; $index++) {
            $clubs[] = $this->createClub($liga, sprintf('Clube %02d', $index));
        }

        $groups = LigaCopaGrupo::query()
            ->with('memberships')
            ->where('liga_id', $liga->id)
            ->orderBy('ordem')
            ->get();

        $this->resolveGroupMatchesBySeed($liga, $groups[0]);

        $this->assertDatabaseMissing('liga_copa_fases', [
            'liga_id' => $liga->id,
            'tipo' => LigaCopaService::PHASE_SEMIFINAL,
        ]);

        $this->resolveGroupMatchesBySeed($liga, $groups[1]);

        $payload = app(LigaCopaService::class)->buildPayload($liga, $clubs[0]);

        $this->assertSame(LigaCopaService::PHASE_SEMIFINAL, $payload['summary']['current_phase_type']);
        $this->assertSame($clubs[0]->id, $payload['groups'][0]['rows'][0]['club_id']);
        $this->assertSame($clubs[1]->id, $payload['groups'][0]['rows'][1]['club_id']);
        $this->assertSame($clubs[4]->id, $payload['groups'][1]['rows'][0]['club_id']);
        $this->assertSame($clubs[5]->id, $payload['groups'][1]['rows'][1]['club_id']);

        $semiPhase = collect($payload['bracket']['phases'])->firstWhere('type', LigaCopaService::PHASE_SEMIFINAL);

        $this->assertNotNull($semiPhase);

        $semiMatches = collect($semiPhase['matches'])
            ->sortBy('slot_order')
            ->values();

        $firstSemiLegOne = collect($semiMatches[0]['legs'])->firstWhere('perna', 1);
        $secondSemiLegOne = collect($semiMatches[1]['legs'])->firstWhere('perna', 1);

        $this->assertSame($clubs[0]->id, $firstSemiLegOne['mandante_id']);
        $this->assertSame($clubs[5]->id, $firstSemiLegOne['visitante_id']);
        $this->assertSame($clubs[4]->id, $secondSemiLegOne['mandante_id']);
        $this->assertSame($clubs[1]->id, $secondSemiLegOne['visitante_id']);
    }

    public function test_liga_classificacao_ignores_cup_matches(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 8]);

        [$clubeA, $clubeB] = LigaClube::withoutEvents(function () use ($liga): array {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            return [
                LigaClube::create([
                    'liga_id' => $liga->id,
                    'confederacao_id' => $liga->confederacao_id,
                    'user_id' => $userA->id,
                    'nome' => 'Clube A',
                ]),
                LigaClube::create([
                    'liga_id' => $liga->id,
                    'confederacao_id' => $liga->confederacao_id,
                    'user_id' => $userB->id,
                    'nome' => 'Clube B',
                ]),
            ];
        });

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $clubeA->id,
            'visitante_id' => $clubeB->id,
            'competition_type' => Partida::COMPETITION_LEAGUE,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 2,
            'placar_visitante' => 0,
        ]);

        Partida::create([
            'liga_id' => $liga->id,
            'mandante_id' => $clubeA->id,
            'visitante_id' => $clubeB->id,
            'competition_type' => Partida::COMPETITION_CUP,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 0,
            'placar_visitante' => 3,
        ]);

        $ranking = app(LigaClassificacaoService::class)->rankingForLiga($liga)->values();

        $this->assertSame($clubeA->id, $ranking[0]['clube_id']);
        $this->assertSame(3, $ranking[0]['pontos']);
        $this->assertSame(1, $ranking[0]['partidas_jogadas']);
        $this->assertSame($clubeB->id, $ranking[1]['clube_id']);
        $this->assertSame(0, $ranking[1]['pontos']);
    }

    public function test_membership_allocation_retries_after_unique_slot_conflict(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 8]);

        for ($index = 1; $index <= 3; $index++) {
            $this->createClub($liga, "Base {$index}");
        }

        $candidate = LigaClube::withoutEvents(function () use ($liga): LigaClube {
            $user = User::factory()->create();
            $user->ligas()->attach($liga->id);

            return LigaClube::create([
                'liga_id' => $liga->id,
                'confederacao_id' => $liga->confederacao_id,
                'user_id' => $user->id,
                'nome' => 'Candidato ao retry',
            ]);
        });

        $groupA = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->where('ordem', 1)
            ->firstOrFail();

        $groupB = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->where('ordem', 2)
            ->firstOrFail();

        $service = Mockery::mock(LigaCopaService::class, [app(PartidaSchedulerService::class)])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('resolveFirstAvailableGroupSlot')
            ->twice()
            ->andReturn(
                ['grupo' => $groupA, 'ordem' => 4],
                ['grupo' => $groupB, 'ordem' => 1],
            );

        $service->shouldReceive('createGroupMembership')
            ->once()
            ->with($groupA, Mockery::on(fn (LigaClube $club): bool => $club->id === $candidate->id), 4)
            ->andThrow($this->makeMembershipConflictException());

        $service->shouldReceive('createGroupMembership')
            ->once()
            ->with($groupB, Mockery::on(fn (LigaClube $club): bool => $club->id === $candidate->id), 1)
            ->andReturnUsing(function (LigaCopaGrupo $grupo, LigaClube $clube, int $ordem): LigaCopaGrupoClube {
                return LigaCopaGrupoClube::query()->create([
                    'grupo_id' => $grupo->id,
                    'liga_clube_id' => $clube->id,
                    'ordem' => $ordem,
                ]);
            });

        $service->handleClubCreated($candidate->fresh());

        $this->assertDatabaseHas('liga_copa_grupo_clubes', [
            'liga_clube_id' => $candidate->id,
            'grupo_id' => $groupB->id,
            'ordem' => 1,
        ]);

        $this->assertSame(
            1,
            LigaCopaGrupoClube::query()
                ->where('liga_clube_id', $candidate->id)
                ->count(),
        );
    }

    private function makeMembershipConflictException(): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(
            config('database.default'),
            'insert into "liga_copa_grupo_clubes" ("grupo_id", "liga_clube_id", "ordem") values (?, ?, ?)',
            [],
            new PDOException('SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "liga_copa_grupo_clubes_grupo_ordem_unique"'),
        );
    }

    private function createCompetitionContext(): array
    {
        $suffix = str_replace('.', '', uniqid('cup', true));

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
            'descricao' => 'Confederacao de teste da copa.',
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        return compact('plataforma', 'jogo', 'geracao', 'confederacao');
    }

    private function createLiga(array $context, array $overrides = []): Liga
    {
        return Liga::create(array_merge([
            'nome' => 'Liga Copa MVP',
            'descricao' => 'Liga de teste da Copa.',
            'regras' => 'Regras de teste.',
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
            'confederacao_id' => $context['confederacao']->id,
            'jogo_id' => $context['jogo']->id,
            'geracao_id' => $context['geracao']->id,
            'plataforma_id' => $context['plataforma']->id,
        ], $overrides));
    }

    private function createClub(Liga $liga, string $name): LigaClube
    {
        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        return LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $user->id,
            'nome' => $name,
        ]);
    }

    private function resolveGroupMatchesBySeed(Liga $liga, LigaCopaGrupo $group): void
    {
        $group->loadMissing('memberships');

        $seedByClubId = $group->memberships
            ->sortBy('ordem')
            ->pluck('ordem', 'liga_clube_id')
            ->map(fn ($ordem) => (int) $ordem)
            ->all();

        $matches = Partida::query()
            ->with('cupMeta')
            ->cupCompetition()
            ->where('liga_id', $liga->id)
            ->whereHas('cupMeta', fn ($query) => $query->where('grupo_id', $group->id))
            ->get();

        foreach ($matches as $match) {
            $homeSeed = $seedByClubId[(int) $match->mandante_id] ?? PHP_INT_MAX;
            $awaySeed = $seedByClubId[(int) $match->visitante_id] ?? PHP_INT_MAX;

            $match->update([
                'estado' => 'placar_confirmado',
                'placar_mandante' => $homeSeed < $awaySeed ? 1 : 0,
                'placar_visitante' => $homeSeed < $awaySeed ? 0 : 1,
            ]);

            app(LigaCopaService::class)->handlePartidaResolved($match->fresh());
        }
    }
}
