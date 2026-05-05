<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubeFinanceiroMovimento;
use App\Models\Partida;
use App\Models\Plataforma;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserDeletionTest extends TestCase
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

    public function test_admin_cannot_delete_own_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Você não pode excluir o seu próprio usuário.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_another_admin_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $anotherAdmin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $anotherAdmin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Usuários administradores não podem ser excluídos.');
        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id]);
    }

    public function test_admin_can_delete_user_with_related_history_and_preserve_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);
        $context = $this->createLeagueContext($target);

        Profile::factory()->create(['user_id' => $target->id]);

        UserDisponibilidade::query()->create([
            'user_id' => $target->id,
            'dia_semana' => 1,
            'hora_inicio' => '10:00:00',
            'hora_fim' => '11:00:00',
        ]);

        $context['liga']->users()->attach($target->id);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', [
                'user' => $target,
                'q' => 'busca',
            ]));

        $response->assertRedirect(route('admin.users.index', ['q' => 'busca']));
        $response->assertSessionHas('success', 'Usuário excluído com sucesso.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('profiles', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('user_disponibilidades', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('liga_jogador', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('liga_clubes', ['id' => $context['targetClub']->id]);
    }

    public function test_admin_can_delete_regular_user_without_history_and_preserve_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', [
                'user' => $target,
                'q' => 'busca',
            ]));

        $response->assertRedirect(route('admin.users.index', ['q' => 'busca']));
        $response->assertSessionHas('success', 'Usuário excluído com sucesso.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_user_hard_delete_cascades_club_partida_and_finance_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);
        $opponent = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);

        $context = $this->createLeagueContext($target, $opponent, $other);
        $targetClub = $context['targetClub'];
        $opponentClub = $context['opponentClub'];
        $otherClub = $context['otherClub'];
        $liga = $context['liga'];

        $partida = Partida::query()->create([
            'liga_id' => $liga->id,
            'mandante_id' => $targetClub->id,
            'visitante_id' => $opponentClub->id,
            'scheduled_at' => now()->addDay(),
            'estado' => 'confirmada',
        ]);

        LigaClubeFinanceiro::query()->create([
            'liga_id' => $liga->id,
            'clube_id' => $targetClub->id,
            'saldo' => 1000,
        ]);

        LigaClubeFinanceiroMovimento::query()->create([
            'liga_id' => $liga->id,
            'clube_id' => $targetClub->id,
            'operacao' => LigaClubeFinanceiroMovimento::OPERATION_CREDIT,
            'descricao' => 'Credito de teste',
            'valor' => 1000,
            'saldo_antes' => 0,
            'saldo_depois' => 1000,
        ]);

        $sharedPartida = Partida::query()->create([
            'liga_id' => $liga->id,
            'mandante_id' => $opponentClub->id,
            'visitante_id' => $otherClub->id,
            'scheduled_at' => now()->addDays(2),
            'estado' => 'finalizada',
            'wo_para_user_id' => $target->id,
            'placar_mandante' => 2,
            'placar_visitante' => 0,
            'placar_registrado_por' => $target->id,
            'placar_registrado_em' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Usuário excluído com sucesso.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('liga_clubes', ['id' => $targetClub->id]);
        $this->assertDatabaseMissing('partidas', ['id' => $partida->id]);
        $this->assertDatabaseMissing('liga_clube_financeiro', ['clube_id' => $targetClub->id]);
        $this->assertDatabaseMissing('liga_clube_financeiro_movimentos', ['clube_id' => $targetClub->id]);
        $this->assertDatabaseHas('users', ['id' => $opponent->id]);
        $this->assertDatabaseHas('liga_clubes', ['id' => $opponentClub->id]);
        $this->assertDatabaseHas('partidas', [
            'id' => $sharedPartida->id,
            'wo_para_user_id' => null,
            'placar_registrado_por' => null,
        ]);
    }

    public function test_admin_user_hard_delete_removes_user_rows_without_foreign_keys(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);
        $control = User::factory()->create(['is_admin' => false]);
        $context = $this->createLeagueContext($target, $control);
        $now = now();

        DB::table('liga_clube_conquistas')->insert([
            [
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['targetClub']->id,
                'user_id' => $target->id,
                'confederacao_id' => $context['confederacao']->id,
                'conquista_id' => 1001,
                'claimed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['opponentClub']->id,
                'user_id' => $control->id,
                'confederacao_id' => $context['confederacao']->id,
                'conquista_id' => 1002,
                'claimed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('liga_clube_patrocinios')->insert([
            [
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['targetClub']->id,
                'user_id' => $target->id,
                'confederacao_id' => $context['confederacao']->id,
                'patrocinio_id' => 2001,
                'claimed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['opponentClub']->id,
                'user_id' => $control->id,
                'confederacao_id' => $context['confederacao']->id,
                'patrocinio_id' => 2002,
                'claimed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('liga_clube_ajustes_salariais')->insert([
            [
                'user_id' => $target->id,
                'confederacao_id' => $context['confederacao']->id,
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['targetClub']->id,
                'liga_clube_elenco_id' => 3001,
                'wage_anterior' => 100,
                'wage_novo' => 200,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $control->id,
                'confederacao_id' => $context['confederacao']->id,
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['opponentClub']->id,
                'liga_clube_elenco_id' => 3002,
                'wage_anterior' => 100,
                'wage_novo' => 200,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('liga_clube_vendas_mercado')->insert([
            [
                'user_id' => $target->id,
                'confederacao_id' => $context['confederacao']->id,
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['targetClub']->id,
                'elencopadrao_id' => 4001,
                'valor_base' => 100,
                'valor_credito' => 80,
                'tax_percent' => 20,
                'tax_value' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $control->id,
                'confederacao_id' => $context['confederacao']->id,
                'liga_id' => $context['liga']->id,
                'liga_clube_id' => $context['opponentClub']->id,
                'elencopadrao_id' => 4002,
                'valor_base' => 100,
                'valor_credito' => 80,
                'tax_percent' => 20,
                'tax_value' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Usuário excluído com sucesso.');
        $this->assertDatabaseMissing('liga_clube_conquistas', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('liga_clube_patrocinios', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('liga_clube_ajustes_salariais', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('liga_clube_vendas_mercado', ['user_id' => $target->id]);
        $this->assertDatabaseHas('liga_clube_conquistas', ['user_id' => $control->id]);
        $this->assertDatabaseHas('liga_clube_patrocinios', ['user_id' => $control->id]);
        $this->assertDatabaseHas('liga_clube_ajustes_salariais', ['user_id' => $control->id]);
        $this->assertDatabaseHas('liga_clube_vendas_mercado', ['user_id' => $control->id]);
    }

    /**
     * @return array{
     *     jogo: Jogo,
     *     geracao: Geracao,
     *     plataforma: Plataforma,
     *     confederacao: Confederacao,
     *     liga: Liga,
     *     targetClub: LigaClube,
     *     opponentClub: LigaClube,
     *     otherClub: LigaClube|null
     * }
     */
    private function createLeagueContext(User $target, ?User $opponent = null, ?User $other = null): array
    {
        $opponent ??= User::factory()->create(['is_admin' => false]);

        $jogo = Jogo::query()->create([
            'nome' => 'FIFA 26',
            'slug' => 'fifa-26',
        ]);

        $geracao = Geracao::query()->create([
            'nome' => 'Geração Teste',
            'slug' => 'geracao-teste',
        ]);

        $plataforma = Plataforma::query()->create([
            'nome' => 'PlayStation 5',
            'slug' => 'playstation-5',
        ]);

        $confederacao = Confederacao::query()->create([
            'nome' => 'Confederação Teste',
            'descricao' => 'Confederação usada nos testes de exclusão.',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::query()->create([
            'nome' => 'Liga Teste',
            'descricao' => 'Liga usada nos testes de exclusão.',
            'regras' => 'Regras de teste.',
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 20,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $targetClub = LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $target->id,
            'nome' => 'Clube do alvo',
        ]);

        $opponentClub = LigaClube::query()->create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $opponent->id,
            'nome' => 'Clube adversario',
        ]);

        $otherClub = null;
        if ($other) {
            $otherClub = LigaClube::query()->create([
                'liga_id' => $liga->id,
                'confederacao_id' => $confederacao->id,
                'user_id' => $other->id,
                'nome' => 'Outro clube',
            ]);
        }

        return [
            'jogo' => $jogo,
            'geracao' => $geracao,
            'plataforma' => $plataforma,
            'confederacao' => $confederacao,
            'liga' => $liga,
            'targetClub' => $targetClub,
            'opponentClub' => $opponentClub,
            'otherClub' => $otherClub,
        ];
    }
}
