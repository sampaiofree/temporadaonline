<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaCopaGrupo;
use App\Models\LigaCopaGrupoClube;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\LigaCopaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LigaCopaCompletionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_payload_reconciles_old_league_clubs_without_existing_cup_memberships(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 8]);

        $clubs = [];
        for ($index = 1; $index <= 4; $index++) {
            $clubs[] = $this->createLegacyClub($liga, sprintf('Clube %02d', $index), $index);
        }

        $payload = app(LigaCopaService::class)->buildPayload($liga, $clubs[0]);

        $grupoA = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->where('ordem', 1)
            ->firstOrFail();

        $this->assertCount(2, $payload['groups']);
        $this->assertSame(4, $grupoA->memberships()->count());
        $this->assertSame(
            [$clubs[0]->id, $clubs[1]->id, $clubs[2]->id, $clubs[3]->id],
            $grupoA->memberships()->orderBy('ordem')->pluck('liga_clube_id')->all(),
        );
        $this->assertSame(
            12,
            Partida::query()
                ->cupCompetition()
                ->where('liga_id', $liga->id)
                ->whereHas('cupMeta', fn ($query) => $query->where('grupo_id', $grupoA->id))
                ->count(),
        );
    }

    public function test_reconcile_preserves_existing_memberships_and_only_adds_missing_clubs(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['max_times' => 8]);

        $clubs = [];
        for ($index = 1; $index <= 4; $index++) {
            $clubs[] = $this->createLegacyClub($liga, sprintf('Clube %02d', $index), $index);
        }

        $grupoA = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->where('ordem', 1)
            ->firstOrFail();

        LigaCopaGrupoClube::query()->create([
            'grupo_id' => $grupoA->id,
            'liga_clube_id' => $clubs[0]->id,
            'ordem' => 1,
        ]);

        $added = app(LigaCopaService::class)->reconcileLigaClubs($liga);

        $memberships = LigaCopaGrupoClube::query()
            ->where('grupo_id', $grupoA->id)
            ->orderBy('ordem')
            ->get();

        $this->assertSame(3, $added);
        $this->assertCount(4, $memberships);
        $this->assertSame(
            [$clubs[0]->id, $clubs[1]->id, $clubs[2]->id, $clubs[3]->id],
            $memberships->pluck('liga_clube_id')->all(),
        );
        $this->assertSame([1, 2, 3, 4], $memberships->pluck('ordem')->all());
        $this->assertSame(
            12,
            Partida::query()
                ->cupCompetition()
                ->where('liga_id', $liga->id)
                ->whereHas('cupMeta', fn ($query) => $query->where('grupo_id', $grupoA->id))
                ->count(),
        );
    }

    public function test_complete_liga_copa_command_normalizes_legacy_liga_and_creates_demo_clubs(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['nome' => 'AA Liga', 'max_times' => 24]);

        for ($index = 1; $index <= 4; $index++) {
            $this->createLegacyClub($liga, sprintf('Clube Atual %02d', $index), $index);
        }

        $this->artisan('liga:copa:complete', [
            'liga_id' => $liga->id,
            '--target-max-times' => 8,
        ])->assertExitCode(0);

        $liga->refresh();
        $groupIds = LigaCopaGrupo::query()->where('liga_id', $liga->id)->pluck('id');

        $this->assertSame(8, (int) $liga->max_times);
        $this->assertSame(2, LigaCopaGrupo::query()->where('liga_id', $liga->id)->count());
        $this->assertSame(8, LigaClube::query()->where('liga_id', $liga->id)->count());
        $this->assertSame(8, LigaCopaGrupoClube::query()->whereIn('grupo_id', $groupIds)->count());
        $this->assertSame(24, Partida::query()->where('liga_id', $liga->id)->cupCompetition()->count());

        $this->assertDatabaseHas('users', ['email' => 'aa-liga-copa-05@mco.local']);
        $this->assertDatabaseHas('users', ['email' => 'aa-liga-copa-08@mco.local']);
        $this->assertDatabaseHas('liga_clubes', ['liga_id' => $liga->id, 'nome' => 'AA Clube 05']);
        $this->assertDatabaseHas('liga_clubes', ['liga_id' => $liga->id, 'nome' => 'AA Clube 08']);
        $this->assertDatabaseHas('profiles', [
            'nickname' => 'AACopa05',
            'jogo_id' => $liga->jogo_id,
            'geracao_id' => $liga->geracao_id,
            'plataforma_id' => $liga->plataforma_id,
        ]);
    }

    public function test_complete_liga_copa_command_aborts_when_cup_progress_already_exists(): void
    {
        $context = $this->createCompetitionContext();
        $liga = $this->createLiga($context, ['nome' => 'AA Liga', 'max_times' => 24]);

        $clube = $this->createLegacyClub($liga, 'Clube Atual 01', 1);
        $grupoA = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->where('ordem', 1)
            ->firstOrFail();

        LigaCopaGrupoClube::query()->create([
            'grupo_id' => $grupoA->id,
            'liga_clube_id' => $clube->id,
            'ordem' => 1,
        ]);

        $this->artisan('liga:copa:complete', [
            'liga_id' => $liga->id,
            '--target-max-times' => 8,
        ])
            ->expectsOutputToContain('A liga já possui clubes alocados na Copa.')
            ->assertExitCode(1);

        $liga->refresh();

        $this->assertSame(24, (int) $liga->max_times);
        $this->assertSame(1, LigaCopaGrupoClube::query()->where('grupo_id', $grupoA->id)->count());
    }

    private function createCompetitionContext(): array
    {
        $suffix = str_replace('.', '', uniqid('cup_complete', true));

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
            'descricao' => 'Confederacao de teste.',
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
            'nome' => 'Liga Copa Legacy',
            'descricao' => 'Liga de teste.',
            'regras' => 'Regras de teste.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 8,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 1000000,
            'multa_multiplicador' => 1.5,
            'cobranca_salario' => false,
            'venda_min_percent' => 70,
            'bloquear_compra_saldo_negativo' => false,
            'confederacao_id' => $context['confederacao']->id,
            'jogo_id' => $context['jogo']->id,
            'geracao_id' => $context['geracao']->id,
            'plataforma_id' => $context['plataforma']->id,
        ], $overrides));
    }

    private function createLegacyClub(Liga $liga, string $name, int $index): LigaClube
    {
        $user = User::factory()->create([
            'email' => sprintf('legacy-club-%02d@example.test', $index),
        ]);

        $user->ligas()->syncWithoutDetaching([$liga->id]);

        return LigaClube::withoutEvents(function () use ($liga, $user, $name): LigaClube {
            return LigaClube::query()->create([
                'liga_id' => $liga->id,
                'confederacao_id' => $liga->confederacao_id,
                'user_id' => $user->id,
                'nome' => $name,
            ]);
        });
    }
}
