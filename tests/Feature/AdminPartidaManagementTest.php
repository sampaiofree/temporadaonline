<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\PartidaEvento;
use App\Models\Plataforma;
use App\Models\ReclamacaoPartida;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPartidaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);
    }

    public function test_admin_can_access_partidas_index_with_combined_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $data = $this->seedPartidasData();

        $response = $this->actingAs($admin)->get(route('admin.partidas.index', [
            'q' => (string) $data['partidaA']->id,
            'estado' => 'confirmada',
            'liga_id' => (string) $data['ligaA']->id,
            'clube_id' => (string) $data['clubeA1']->id,
            'data_inicio' => '2026-03-01',
            'data_fim' => '2026-03-10',
        ]));

        $response->assertOk();
        $response->assertSee(route('admin.partidas.edit', $data['partidaA']), false);
        $response->assertDontSee(route('admin.partidas.edit', $data['partidaB']), false);
    }

    public function test_admin_can_update_partida_without_side_effect_events(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $data = $this->seedPartidasData();

        $eventosBefore = PartidaEvento::query()->count();

        $response = $this->actingAs($admin)->put(route('admin.partidas.update', [
            'partida' => $data['partidaA'],
            'q' => 'filtro-atual',
        ]), [
            'estado' => 'wo',
            'placar_mandante' => '0',
            'placar_visitante' => '3',
            'wo_para_user_id' => (string) $data['visitanteUserA']->id,
            'wo_motivo' => 'nao_compareceu',
        ]);

        $response
            ->assertRedirect(route('admin.partidas.index', ['q' => 'filtro-atual']))
            ->assertSessionHas('success', 'Partida atualizada com sucesso.');

        $this->assertDatabaseHas('partidas', [
            'id' => $data['partidaA']->id,
            'estado' => 'wo',
            'placar_mandante' => 0,
            'placar_visitante' => 3,
            'wo_para_user_id' => $data['visitanteUserA']->id,
            'wo_motivo' => 'nao_compareceu',
        ]);

        $this->assertSame($eventosBefore, PartidaEvento::query()->count());
    }

    public function test_non_admin_cannot_access_partidas_admin_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $data = $this->seedPartidasData();

        $this->actingAs($user)
            ->get(route('admin.partidas.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.partidas.edit', $data['partidaA']))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.partidas.update', $data['partidaA']), [
                'estado' => 'confirmada',
                'placar_mandante' => '',
                'placar_visitante' => '',
                'wo_para_user_id' => '',
                'wo_motivo' => '',
            ])
            ->assertForbidden();
    }

    public function test_reclamacoes_page_can_filter_by_partida_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $data = $this->seedPartidasData();

        ReclamacaoPartida::query()->create([
            'partida_id' => $data['partidaA']->id,
            'user_id' => $data['mandanteUserA']->id,
            'motivo' => 'outro',
            'descricao' => 'reclamacao_a',
            'status' => 'aberta',
        ]);

        ReclamacaoPartida::query()->create([
            'partida_id' => $data['partidaB']->id,
            'user_id' => $data['mandanteUserB']->id,
            'motivo' => 'outro',
            'descricao' => 'reclamacao_b',
            'status' => 'aberta',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.partidas-reclamacoes.index', ['partida_id' => $data['partidaA']->id]));

        $response->assertOk();
        $response->assertSeeText('reclamacao_a');
        $response->assertDontSeeText('reclamacao_b');
    }

    private function seedPartidasData(): array
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

        $ligaA = Liga::query()->create([
            'nome' => "Liga A {$suffix}",
            'descricao' => 'Descricao A',
            'regras' => 'Regras A',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'confederacao_id' => $confederacao->id,
        ]);

        $ligaB = Liga::query()->create([
            'nome' => "Liga B {$suffix}",
            'descricao' => 'Descricao B',
            'regras' => 'Regras B',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'confederacao_id' => $confederacao->id,
        ]);

        $mandanteUserA = User::factory()->create();
        $visitanteUserA = User::factory()->create();
        $mandanteUserB = User::factory()->create();
        $visitanteUserB = User::factory()->create();

        $clubeA1 = LigaClube::query()->create([
            'liga_id' => $ligaA->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $mandanteUserA->id,
            'nome' => 'Clube A1',
        ]);

        $clubeA2 = LigaClube::query()->create([
            'liga_id' => $ligaA->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $visitanteUserA->id,
            'nome' => 'Clube A2',
        ]);

        $clubeB1 = LigaClube::query()->create([
            'liga_id' => $ligaB->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $mandanteUserB->id,
            'nome' => 'Clube B1',
        ]);

        $clubeB2 = LigaClube::query()->create([
            'liga_id' => $ligaB->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $visitanteUserB->id,
            'nome' => 'Clube B2',
        ]);

        $partidaA = Partida::query()->create([
            'liga_id' => $ligaA->id,
            'mandante_id' => $clubeA1->id,
            'visitante_id' => $clubeA2->id,
            'estado' => 'confirmada',
            'scheduled_at' => '2026-03-05 20:00:00',
        ]);

        $partidaB = Partida::query()->create([
            'liga_id' => $ligaB->id,
            'mandante_id' => $clubeB1->id,
            'visitante_id' => $clubeB2->id,
            'estado' => 'finalizada',
            'scheduled_at' => '2026-04-12 20:00:00',
        ]);

        return [
            'ligaA' => $ligaA,
            'ligaB' => $ligaB,
            'clubeA1' => $clubeA1,
            'clubeA2' => $clubeA2,
            'clubeB1' => $clubeB1,
            'clubeB2' => $clubeB2,
            'partidaA' => $partidaA,
            'partidaB' => $partidaB,
            'mandanteUserA' => $mandanteUserA,
            'visitanteUserA' => $visitanteUserA,
            'mandanteUserB' => $mandanteUserB,
            'visitanteUserB' => $visitanteUserB,
        ];
    }
}
