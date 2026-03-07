<?php

namespace Tests\Feature;

use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConfederacaoMatchRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_confederacao_with_match_reward_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $jogo = Jogo::create(['nome' => 'FC 26', 'slug' => 'fc26']);
        $geracao = Geracao::create(['nome' => 'Nova', 'slug' => 'nova']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.confederacoes.store'), [
                'nome' => 'Confederação Teste',
                'descricao' => 'Descrição teste',
                'timezone' => 'America/Sao_Paulo',
                'ganho_vitoria_partida' => 900000,
                'ganho_empate_partida' => 400000,
                'ganho_derrota_partida' => 100000,
                'jogo_id' => $jogo->id,
                'geracao_id' => $geracao->id,
                'periodos' => [],
                'leiloes' => [],
            ]);

        $response
            ->assertRedirect(route('admin.confederacoes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('confederacoes', [
            'nome' => 'Confederação Teste',
            'ganho_vitoria_partida' => 900000,
            'ganho_empate_partida' => 400000,
            'ganho_derrota_partida' => 100000,
        ]);
    }

    public function test_admin_confederacao_rewards_reject_negative_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $jogo = Jogo::create(['nome' => 'FC 26', 'slug' => 'fc26']);
        $geracao = Geracao::create(['nome' => 'Nova', 'slug' => 'nova']);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.confederacoes.create'))
            ->post(route('admin.confederacoes.store'), [
                'nome' => 'Confederação Inválida',
                'descricao' => 'Descrição teste',
                'timezone' => 'America/Sao_Paulo',
                'ganho_vitoria_partida' => -1,
                'ganho_empate_partida' => 0,
                'ganho_derrota_partida' => 0,
                'jogo_id' => $jogo->id,
                'geracao_id' => $geracao->id,
                'periodos' => [],
                'leiloes' => [],
            ]);

        $response
            ->assertRedirect(route('admin.confederacoes.create'))
            ->assertSessionHasErrors(['ganho_vitoria_partida']);

        $this->assertDatabaseMissing('confederacoes', [
            'nome' => 'Confederação Inválida',
        ]);
    }
}
