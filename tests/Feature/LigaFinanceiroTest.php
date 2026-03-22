<?php

namespace Tests\Feature;

use App\Models\Elencopadrao;
use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaFolhaPagamento;
use App\Models\LigaPeriodo;
use App\Models\LigaTransferencia;
use App\Models\Plataforma;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LigaFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private function createLiga(array $overrides = []): Liga
    {
        $plataforma = Plataforma::create(['nome' => 'PlayStation 5', 'slug' => 'ps5']);
        $jogo = Jogo::create(['nome' => 'FC26', 'slug' => 'fc26']);
        $geracao = Geracao::create(['nome' => 'Nova', 'slug' => 'nova']);
        $confederacao = Confederacao::create([
            'nome' => 'Confederacao Teste',
            'descricao' => 'Confederacao de teste.',
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::create(array_merge([
            'nome' => 'Liga Teste',
            'descricao' => 'Liga de teste.',
            'regras' => 'Regras de teste.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 1000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ], $overrides));

        LigaPeriodo::create([
            'confederacao_id' => $confederacao->id,
            'inicio' => Carbon::now('America/Sao_Paulo')->subDay(),
            'fim' => Carbon::now('America/Sao_Paulo')->addDay(),
        ]);

        return $liga;
    }

    private function createElenco(Jogo $jogo, array $overrides = []): Elencopadrao
    {
        return Elencopadrao::create(array_merge([
            'jogo_id' => $jogo->id,
            'long_name' => 'Player '.uniqid(),
            'short_name' => 'P'.rand(1, 999),
            'value_eur' => 0,
            'wage_eur' => 0,
        ], $overrides));
    }

    public function test_nao_permite_comprar_se_ja_tem_18(): void
    {
        $liga = $this->createLiga([
            'saldo_inicial' => 999999,
            'max_jogadores_por_clube' => 18,
        ]);

        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        $clube = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $user->id,
            'nome' => 'Clube A',
        ]);

        $jogo = Jogo::findOrFail($liga->jogo_id);

        for ($i = 0; $i < 18; $i++) {
            $player = $this->createElenco($jogo, [
                'long_name' => "Player {$i}",
            ]);

            LigaClubeElenco::create([
                'confederacao_id' => $liga->confederacao_id,
                'liga_id' => $liga->id,
                'liga_clube_id' => $clube->id,
                'elencopadrao_id' => $player->id,
                'value_eur' => 0,
                'wage_eur' => 0,
                'ativo' => true,
            ]);
        }

        $playerExtra = $this->createElenco($jogo, ['long_name' => 'Player Extra']);

        $response = $this
            ->actingAs($user)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clube->id}/comprar", [
                'elencopadrao_id' => $playerExtra->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_nao_permite_jogador_duplicado_na_liga(): void
    {
        $liga = $this->createLiga(['saldo_inicial' => 999999]);
        $jogo = Jogo::findOrFail($liga->jogo_id);
        $player = $this->createElenco($jogo, ['long_name' => 'Duplicado']);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userA->ligas()->attach($liga->id);
        $userB->ligas()->attach($liga->id);

        $clubeA = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $userA->id,
            'nome' => 'Clube A',
        ]);

        $clubeB = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $userB->id,
            'nome' => 'Clube B',
        ]);

        $this
            ->actingAs($userA)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeA->id}/comprar", [
                'elencopadrao_id' => $player->id,
            ])
            ->assertStatus(201);

        $this
            ->actingAs($userB)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeB->id}/comprar", [
                'elencopadrao_id' => $player->id,
            ])
            ->assertStatus(409);
    }

    public function test_compra_debita_saldo_corretamente(): void
    {
        $liga = $this->createLiga(['saldo_inicial' => 1000]);
        $jogo = Jogo::findOrFail($liga->jogo_id);
        $player = $this->createElenco($jogo, [
            'long_name' => 'Compra',
            'value_eur' => 200,
            'wage_eur' => 10,
        ]);

        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        $clube = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $user->id,
            'nome' => 'Clube A',
        ]);

        $this
            ->actingAs($user)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clube->id}/comprar", [
                'elencopadrao_id' => $player->id,
            ])
            ->assertStatus(201);

        $wallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clube->id)
            ->firstOrFail();

        $this->assertSame(800, (int) $wallet->saldo);

        $this->assertTrue(LigaTransferencia::where('liga_id', $liga->id)
            ->where('elencopadrao_id', $player->id)
            ->where('tipo', 'jogador_livre')
            ->exists());
    }

    public function test_venda_credita_vendedor_corretamente(): void
    {
        $liga = $this->createLiga(['saldo_inicial' => 1000, 'venda_min_percent' => 100]);
        $jogo = Jogo::findOrFail($liga->jogo_id);
        $player = $this->createElenco($jogo, [
            'long_name' => 'Venda',
            'value_eur' => 200,
            'wage_eur' => 10,
        ]);

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $seller->ligas()->attach($liga->id);
        $buyer->ligas()->attach($liga->id);

        $clubeSeller = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $seller->id,
            'nome' => 'Clube Seller',
        ]);

        $clubeBuyer = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $buyer->id,
            'nome' => 'Clube Buyer',
        ]);

        LigaClubeElenco::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clubeSeller->id,
            'elencopadrao_id' => $player->id,
            'value_eur' => 200,
            'wage_eur' => 10,
            'ativo' => true,
        ]);

        $price = 250;

        $this
            ->actingAs($buyer)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeBuyer->id}/vender", [
                'elencopadrao_id' => $player->id,
                'price' => $price,
            ])
            ->assertOk();

        $sellerWallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeSeller->id)
            ->firstOrFail();

        $buyerWallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeBuyer->id)
            ->firstOrFail();

        $this->assertSame(1000 + $price, (int) $sellerWallet->saldo);
        $this->assertSame(1000 - $price, (int) $buyerWallet->saldo);

        $this->assertTrue(LigaTransferencia::where('liga_id', $liga->id)
            ->where('elencopadrao_id', $player->id)
            ->where('tipo', 'venda')
            ->exists());
    }

    public function test_multa_move_jogador_mesmo_sem_consentimento(): void
    {
        $plataforma = Plataforma::create(['nome' => 'PlayStation 5', 'slug' => 'ps5']);
        $jogo = Jogo::create(['nome' => 'FC26', 'slug' => 'fc26']);
        $geracao = Geracao::create(['nome' => 'Nova', 'slug' => 'nova']);
        $confederacao = Confederacao::create([
            'nome' => 'Confederacao Teste Multa',
            'descricao' => 'Confederacao de teste',
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::create([
            'nome' => 'Liga Teste',
            'descricao' => 'Liga de teste.',
            'regras' => 'Regras de teste.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 5000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        LigaPeriodo::create([
            'confederacao_id' => $confederacao->id,
            'inicio' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'fim' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);

        $player = $this->createElenco($jogo, [
            'long_name' => 'Multa',
            'value_eur' => 700,
            'wage_eur' => 20,
        ]);

        $owner = User::factory()->create();
        $buyer = User::factory()->create();
        $owner->ligas()->attach($liga->id);
        $buyer->ligas()->attach($liga->id);

        $clubeOwner = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $owner->id,
            'nome' => 'Clube Dono',
        ]);

        $clubeBuyer = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $buyer->id,
            'nome' => 'Clube Comprador',
        ]);

        $entry = LigaClubeElenco::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clubeOwner->id,
            'elencopadrao_id' => $player->id,
            'value_eur' => 1000,
            'wage_eur' => 20,
            'ativo' => true,
        ]);

        $this
            ->actingAs($buyer)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeBuyer->id}/multa", [
                'elencopadrao_id' => $player->id,
            ])
            ->assertOk();

        $entry->refresh();
        $this->assertSame($clubeBuyer->id, (int) $entry->liga_clube_id);

        $multa = 1000;

        $ownerWallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeOwner->id)
            ->firstOrFail();

        $buyerWallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeBuyer->id)
            ->firstOrFail();

        $this->assertSame(5000 + $multa, (int) $ownerWallet->saldo);
        $this->assertSame(5000 - $multa, (int) $buyerWallet->saldo);

        $this->assertTrue(LigaTransferencia::where('liga_id', $liga->id)
            ->where('elencopadrao_id', $player->id)
            ->where('tipo', 'multa')
            ->where('valor', $multa)
            ->exists());
    }

    public function test_multa_aplica_multiplicador_quando_entry_igual_ao_valor_original(): void
    {
        $plataforma = Plataforma::create(['nome' => 'PlayStation 5', 'slug' => 'ps5']);
        $jogo = Jogo::create(['nome' => 'FC26', 'slug' => 'fc26']);
        $geracao = Geracao::create(['nome' => 'Nova', 'slug' => 'nova']);
        $confederacao = Confederacao::create([
            'nome' => 'Confederacao Teste Multa Multiplicador',
            'descricao' => 'Confederacao de teste',
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::create([
            'nome' => 'Liga Teste',
            'descricao' => 'Liga de teste.',
            'regras' => 'Regras de teste.',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 5000,
            'multa_multiplicador' => 1.50,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        LigaPeriodo::create([
            'confederacao_id' => $confederacao->id,
            'inicio' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'fim' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);

        $player = $this->createElenco($jogo, [
            'long_name' => 'Multa Multiplicada',
            'value_eur' => 700,
            'wage_eur' => 20,
        ]);

        $owner = User::factory()->create();
        $buyer = User::factory()->create();
        $owner->ligas()->attach($liga->id);
        $buyer->ligas()->attach($liga->id);

        $clubeOwner = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $owner->id,
            'nome' => 'Clube Dono',
        ]);

        $clubeBuyer = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $buyer->id,
            'nome' => 'Clube Comprador',
        ]);

        $entry = LigaClubeElenco::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clubeOwner->id,
            'elencopadrao_id' => $player->id,
            'value_eur' => 700,
            'wage_eur' => 20,
            'ativo' => true,
        ]);

        $this
            ->actingAs($buyer)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeBuyer->id}/multa", [
                'elencopadrao_id' => $player->id,
            ])
            ->assertOk();

        $entry->refresh();
        $this->assertSame($clubeBuyer->id, (int) $entry->liga_clube_id);

        $multa = 1050;

        $ownerWallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeOwner->id)
            ->firstOrFail();

        $buyerWallet = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeBuyer->id)
            ->firstOrFail();

        $this->assertSame(5000 + $multa, (int) $ownerWallet->saldo);
        $this->assertSame(5000 - $multa, (int) $buyerWallet->saldo);

        $this->assertTrue(LigaTransferencia::where('liga_id', $liga->id)
            ->where('elencopadrao_id', $player->id)
            ->where('tipo', 'multa')
            ->where('valor', $multa)
            ->exists());
    }

    public function test_cobranca_manual_por_rodada_esta_desativada(): void
    {
        $liga = $this->createLiga(['saldo_inicial' => 1000]);

        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        $this
            ->actingAs($user)
            ->postJson("/api/ligas/{$liga->id}/rodadas/1/cobrar-salarios")
            ->assertStatus(410)
            ->assertJsonPath('message', 'Cobrança por rodada desativada. Use a cobrança automática por partida.');
    }

    public function test_salario_pode_ficar_negativo_mas_bloqueia_compra_e_multa(): void
    {
        $liga = $this->createLiga([
            'saldo_inicial' => 50,
            'bloquear_compra_saldo_negativo' => true,
            'multa_multiplicador' => 2.00,
        ]);

        $jogo = Jogo::findOrFail($liga->jogo_id);

        $owner = User::factory()->create();
        $buyer = User::factory()->create();
        $owner->ligas()->attach($liga->id);
        $buyer->ligas()->attach($liga->id);

        $clubeOwner = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $owner->id,
            'nome' => 'Clube Dono',
        ]);

        $clubeBuyer = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $buyer->id,
            'nome' => 'Clube Comprador',
        ]);

        LigaClubeFinanceiro::query()->updateOrCreate(
            [
                'liga_id' => $liga->id,
                'clube_id' => $clubeBuyer->id,
            ],
            [
                'saldo' => -30,
            ],
        );

        $free = $this->createElenco($jogo, ['long_name' => 'Livre', 'value_eur' => 10]);

        $this
            ->actingAs($buyer)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeBuyer->id}/comprar", [
                'elencopadrao_id' => $free->id,
            ])
            ->assertStatus(422);

        $victim = $this->createElenco($jogo, ['long_name' => 'Multa Victim', 'value_eur' => 10]);

        $entryVictim = LigaClubeElenco::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clubeOwner->id,
            'elencopadrao_id' => $victim->id,
            'value_eur' => 10,
            'wage_eur' => 0,
            'ativo' => true,
        ]);

        $this
            ->actingAs($buyer)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeBuyer->id}/multa", [
                'elencopadrao_id' => $victim->id,
            ])
            ->assertStatus(422);

        $entryVictim->refresh();
        $this->assertSame($clubeOwner->id, (int) $entryVictim->liga_clube_id);
    }

    public function test_troca_com_ajuste_valor_debita_pagador_e_cria_dois_registros(): void
    {
        $liga = $this->createLiga([
            'saldo_inicial' => 1000,
        ]);

        $jogo = Jogo::findOrFail($liga->jogo_id);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userA->ligas()->attach($liga->id);
        $userB->ligas()->attach($liga->id);

        $clubeA = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $userA->id,
            'nome' => 'Clube A',
        ]);

        $clubeB = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $liga->confederacao_id,
            'user_id' => $userB->id,
            'nome' => 'Clube B',
        ]);

        $playerA = $this->createElenco($jogo, ['long_name' => 'Troca A', 'value_eur' => 100]);
        $playerB = $this->createElenco($jogo, ['long_name' => 'Troca B', 'value_eur' => 100]);

        $entryA = LigaClubeElenco::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clubeA->id,
            'elencopadrao_id' => $playerA->id,
            'value_eur' => 100,
            'wage_eur' => 0,
            'ativo' => true,
        ]);

        $entryB = LigaClubeElenco::create([
            'confederacao_id' => $liga->confederacao_id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clubeB->id,
            'elencopadrao_id' => $playerB->id,
            'value_eur' => 100,
            'wage_eur' => 0,
            'ativo' => true,
        ]);

        $ajuste = 100;

        $this
            ->actingAs($userA)
            ->postJson("/api/ligas/{$liga->id}/clubes/{$clubeA->id}/trocar", [
                'jogador_a_id' => $playerA->id,
                'clube_b_id' => $clubeB->id,
                'jogador_b_id' => $playerB->id,
                'ajuste_valor' => $ajuste,
            ])
            ->assertOk();

        $entryA->refresh();
        $entryB->refresh();

        $this->assertSame($clubeB->id, (int) $entryA->liga_clube_id);
        $this->assertSame($clubeA->id, (int) $entryB->liga_clube_id);

        $walletA = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeA->id)
            ->firstOrFail();

        $walletB = LigaClubeFinanceiro::where('liga_id', $liga->id)
            ->where('clube_id', $clubeB->id)
            ->firstOrFail();

        $this->assertSame(1000 - $ajuste, (int) $walletA->saldo);
        $this->assertSame(1000 + $ajuste, (int) $walletB->saldo);

        $this->assertSame(
            2,
            LigaTransferencia::where('liga_id', $liga->id)->where('tipo', 'troca')->count(),
        );
    }
}
