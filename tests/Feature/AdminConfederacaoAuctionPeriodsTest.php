<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\LigaLeilao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConfederacaoAuctionPeriodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_confederacao_with_auction_windows_with_time(): void
    {
        [$admin, $jogo, $geracao] = $this->adminDependencies();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.confederacoes.store'), [
                'nome' => 'Confederação Leilão',
                'descricao' => 'Janela com horário',
                'timezone' => 'America/Sao_Paulo',
                'ganho_vitoria_partida' => 900000,
                'ganho_empate_partida' => 400000,
                'ganho_derrota_partida' => 100000,
                'jogo_id' => $jogo->id,
                'geracao_id' => $geracao->id,
                'periodos' => [],
                'roubos_multa' => [],
                'leiloes' => [
                    ['inicio' => '2026-03-20T09:15', 'fim' => '2026-03-20T18:45'],
                ],
            ]);

        $response
            ->assertRedirect(route('admin.confederacoes.index'))
            ->assertSessionHas('success');

        $confederacao = Confederacao::query()->where('nome', 'Confederação Leilão')->firstOrFail();

        $this->assertDatabaseHas('liga_leiloes', [
            'confederacao_id' => $confederacao->id,
            'inicio' => '2026-03-20 09:15:00',
            'fim' => '2026-03-20 18:45:00',
        ]);
    }

    public function test_admin_can_replace_existing_auction_windows_when_updating_confederacao(): void
    {
        [$admin, $jogo, $geracao] = $this->adminDependencies();

        $confederacao = Confederacao::create([
            'nome' => 'Confederação Atualização',
            'descricao' => 'Teste',
            'timezone' => 'America/Sao_Paulo',
            'ganho_vitoria_partida' => 800000,
            'ganho_empate_partida' => 300000,
            'ganho_derrota_partida' => 100000,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
        ]);

        LigaLeilao::create([
            'confederacao_id' => $confederacao->id,
            'inicio' => '2026-03-20 09:00:00',
            'fim' => '2026-03-20 18:00:00',
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.confederacoes.update', $confederacao), [
                'nome' => 'Confederação Atualização',
                'descricao' => 'Teste atualizado',
                'timezone' => 'America/Sao_Paulo',
                'ganho_vitoria_partida' => 800000,
                'ganho_empate_partida' => 300000,
                'ganho_derrota_partida' => 100000,
                'jogo_id' => $jogo->id,
                'geracao_id' => $geracao->id,
                'periodos' => [],
                'roubos_multa' => [],
                'leiloes' => [
                    ['inicio' => '2026-03-21T10:00', 'fim' => '2026-03-21T12:30'],
                    ['inicio' => '2026-03-22T14:00', 'fim' => '2026-03-22T19:45'],
                ],
            ]);

        $response
            ->assertRedirect(route('admin.confederacoes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('liga_leiloes', [
            'confederacao_id' => $confederacao->id,
            'inicio' => '2026-03-20 09:00:00',
            'fim' => '2026-03-20 18:00:00',
        ]);

        $this->assertDatabaseCount('liga_leiloes', 2);
        $this->assertDatabaseHas('liga_leiloes', [
            'confederacao_id' => $confederacao->id,
            'inicio' => '2026-03-21 10:00:00',
            'fim' => '2026-03-21 12:30:00',
        ]);
        $this->assertDatabaseHas('liga_leiloes', [
            'confederacao_id' => $confederacao->id,
            'inicio' => '2026-03-22 14:00:00',
            'fim' => '2026-03-22 19:45:00',
        ]);
    }

    public function test_admin_confederacao_rejects_overlapping_auction_windows_with_time(): void
    {
        [$admin, $jogo, $geracao] = $this->adminDependencies();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.confederacoes.create'))
            ->post(route('admin.confederacoes.store'), [
                'nome' => 'Confederação Sobreposição',
                'descricao' => 'Teste',
                'timezone' => 'America/Sao_Paulo',
                'ganho_vitoria_partida' => 900000,
                'ganho_empate_partida' => 400000,
                'ganho_derrota_partida' => 100000,
                'jogo_id' => $jogo->id,
                'geracao_id' => $geracao->id,
                'periodos' => [],
                'roubos_multa' => [],
                'leiloes' => [
                    ['inicio' => '2026-03-20T09:00', 'fim' => '2026-03-20T12:00'],
                    ['inicio' => '2026-03-20T11:30', 'fim' => '2026-03-20T14:00'],
                ],
            ]);

        $response
            ->assertRedirect(route('admin.confederacoes.create'))
            ->assertSessionHasErrors(['leiloes']);

        $this->assertDatabaseMissing('confederacoes', [
            'nome' => 'Confederação Sobreposição',
        ]);
    }

    public function test_admin_confederacao_rejects_auction_window_when_start_is_after_end(): void
    {
        [$admin, $jogo, $geracao] = $this->adminDependencies();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.confederacoes.create'))
            ->post(route('admin.confederacoes.store'), [
                'nome' => 'Confederação Intervalo Inválido',
                'descricao' => 'Teste',
                'timezone' => 'America/Sao_Paulo',
                'ganho_vitoria_partida' => 900000,
                'ganho_empate_partida' => 400000,
                'ganho_derrota_partida' => 100000,
                'jogo_id' => $jogo->id,
                'geracao_id' => $geracao->id,
                'periodos' => [],
                'roubos_multa' => [],
                'leiloes' => [
                    ['inicio' => '2026-03-20T18:01', 'fim' => '2026-03-20T18:00'],
                ],
            ]);

        $response
            ->assertRedirect(route('admin.confederacoes.create'))
            ->assertSessionHasErrors(['leiloes']);

        $this->assertDatabaseMissing('confederacoes', [
            'nome' => 'Confederação Intervalo Inválido',
        ]);
    }

    /**
     * @return array{0:User,1:Jogo,2:Geracao}
     */
    private function adminDependencies(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $jogo = Jogo::create(['nome' => 'FC 26', 'slug' => 'fc26']);
        $geracao = Geracao::create(['nome' => 'Nova', 'slug' => 'nova']);

        return [$admin, $jogo, $geracao];
    }
}
