<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaTransferencia;
use App\Models\Plataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyTransferHistoryDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_transfer_history_endpoint_returns_confederacao_transfers_ordered_and_paginated(): void
    {
        ['liga' => $ligaA, 'confederacao' => $confederacaoA, 'jogo' => $jogoA] = $this->createLeagueContext('A');
        ['liga' => $ligaB, 'confederacao' => $confederacaoB] = $this->createLeagueContext('B');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach([$ligaA->id, $ligaB->id]);

        $clubOne = $this->createClub($ligaA, $confederacaoA, 'Clube Um');
        $clubTwo = $this->createClub($ligaA, $confederacaoA, 'Clube Dois');
        $clubThree = $this->createClub($ligaA, $confederacaoA, 'Clube Tres');
        $clubOtherConf = $this->createClub($ligaB, $confederacaoB, 'Clube Fora');

        $timestamps = [
            now()->subMinutes(60),
            now()->subMinutes(50),
            now()->subMinutes(40),
            now()->subMinutes(30),
            now()->subMinutes(20),
            now()->subMinutes(10),
        ];

        $this->createTransfer($ligaA, $confederacaoA, $jogoA, 'Compra 1', [
            'clube_destino_id' => $clubOne->id,
            'tipo' => 'jogador_livre',
            'observacao' => 'Jogador livre adquirido no mercado.',
        ], $timestamps[0]);

        $this->createTransfer($ligaA, $confederacaoA, $jogoA, 'Compra 2', [
            'clube_origem_id' => $clubOne->id,
            'clube_destino_id' => $clubTwo->id,
            'tipo' => 'venda',
            'observacao' => 'Venda de jogador entre clubes.',
        ], $timestamps[1]);

        $this->createTransfer($ligaA, $confederacaoA, $jogoA, 'Multa 1', [
            'clube_origem_id' => $clubTwo->id,
            'clube_destino_id' => $clubThree->id,
            'tipo' => 'multa',
            'observacao' => 'Multa paga via clausula de rescisao.',
        ], $timestamps[2]);

        $this->createTransfer($ligaA, $confederacaoA, $jogoA, 'Troca 1', [
            'clube_origem_id' => $clubThree->id,
            'clube_destino_id' => $clubOne->id,
            'tipo' => 'troca',
            'observacao' => 'Proposta aceita #99',
        ], $timestamps[3]);

        $this->createTransfer($ligaA, $confederacaoA, $jogoA, 'Leilao 1', [
            'clube_destino_id' => $clubTwo->id,
            'tipo' => 'jogador_livre',
            'observacao' => 'Jogador livre adquirido via leilão.',
        ], $timestamps[4]);

        $newestTransfer = $this->createTransfer($ligaA, $confederacaoA, $jogoA, 'Compra 3', [
            'clube_destino_id' => $clubThree->id,
            'tipo' => 'jogador_livre',
            'observacao' => 'Jogador livre adquirido no mercado.',
        ], $timestamps[5]);

        $this->createTransfer($ligaB, $confederacaoB, $jogoA, 'Outra conf', [
            'clube_destino_id' => $clubOtherConf->id,
            'tipo' => 'jogador_livre',
            'observacao' => 'Jogador livre adquirido no mercado.',
        ], now()->subMinute());

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.transfer_history.data', [
                'confederacao_id' => $confederacaoA->id,
                'page' => 1,
                'per_page' => 5,
            ]));

        $response->assertOk();
        $response->assertJsonPath('history.pagination.page', 1);
        $response->assertJsonPath('history.pagination.per_page', 5);
        $response->assertJsonPath('history.pagination.total', 6);
        $response->assertJsonPath('history.pagination.has_more', true);

        $items = $response->json('history.items');
        $this->assertCount(5, $items);
        $this->assertSame($newestTransfer->id, $items[0]['id'] ?? null);
        $this->assertSame('COMPRA', $items[0]['tipo_label'] ?? null);
        $this->assertSame('Leilao 1', $items[1]['player']['short_name'] ?? null);
        $this->assertSame('LEILAO', $items[1]['tipo_label'] ?? null);
    }

    public function test_transfer_history_formats_contextual_origins_and_troca_label(): void
    {
        ['liga' => $liga, 'confederacao' => $confederacao, 'jogo' => $jogo] = $this->createLeagueContext('C');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach([$liga->id]);

        $clubOne = $this->createClub($liga, $confederacao, 'Origem FC');
        $clubTwo = $this->createClub($liga, $confederacao, 'Destino FC');

        $this->createTransfer($liga, $confederacao, $jogo, 'Livre Mercado', [
            'clube_destino_id' => $clubOne->id,
            'tipo' => 'jogador_livre',
            'observacao' => 'Jogador livre adquirido no mercado.',
        ], now()->subMinutes(3));

        $this->createTransfer($liga, $confederacao, $jogo, 'Livre Leilao', [
            'clube_destino_id' => $clubTwo->id,
            'tipo' => 'jogador_livre',
            'observacao' => 'Jogador livre adquirido via leilão.',
        ], now()->subMinutes(2));

        $this->createTransfer($liga, $confederacao, $jogo, 'Jogador Troca', [
            'clube_origem_id' => $clubOne->id,
            'clube_destino_id' => $clubTwo->id,
            'tipo' => 'troca',
            'observacao' => 'Proposta aceita #100',
        ], now()->subMinute());

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.transfer_history.data', [
                'confederacao_id' => $confederacao->id,
                'page' => 1,
                'per_page' => 10,
            ]));

        $response->assertOk();

        $items = collect($response->json('history.items'));
        $troca = $items->firstWhere('tipo_label', 'TROCA');
        $leilao = $items->firstWhere('tipo_label', 'LEILAO');
        $compraLivre = $items
            ->where('tipo_label', 'COMPRA')
            ->firstWhere('origem.name', 'Mercado Livre');

        $this->assertNotNull($troca);
        $this->assertSame('Origem FC', $troca['origem']['name'] ?? null);
        $this->assertSame('Destino FC', $troca['destino']['name'] ?? null);

        $this->assertNotNull($leilao);
        $this->assertSame('Leilao', $leilao['origem']['name'] ?? null);
        $this->assertSame('auction', $leilao['origem']['kind'] ?? null);

        $this->assertNotNull($compraLivre);
        $this->assertSame('market_free', $compraLivre['origem']['kind'] ?? null);
    }

    /**
     * @return array{liga:Liga, confederacao:Confederacao, jogo:Jogo}
     */
    private function createLeagueContext(string $suffix): array
    {
        $unique = Str::lower(Str::random(6)).Str::lower($suffix);

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
            'timezone' => 'America/Sao_Paulo',
        ]);

        $liga = Liga::create([
            'nome' => "Liga {$unique}",
            'descricao' => 'Liga de teste',
            'regras' => 'Regras de teste',
            'imagem' => null,
            'tipo' => 'publica',
            'status' => 'ativa',
            'max_times' => 16,
            'max_jogadores_por_clube' => 18,
            'saldo_inicial' => 1000000,
            'multa_multiplicador' => 2.00,
            'cobranca_salario' => 'rodada',
            'venda_min_percent' => 100,
            'bloquear_compra_saldo_negativo' => true,
            'confederacao_id' => $confederacao->id,
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        return [
            'liga' => $liga,
            'confederacao' => $confederacao,
            'jogo' => $jogo,
        ];
    }

    private function createClub(Liga $liga, Confederacao $confederacao, string $name): LigaClube
    {
        return LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => User::factory()->create()->id,
            'nome' => $name,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTransfer(Liga $liga, Confederacao $confederacao, Jogo $jogo, string $playerName, array $overrides, \Illuminate\Support\Carbon $createdAt): LigaTransferencia
    {
        $suffix = Str::lower(Str::random(8));

        $player = Elencopadrao::create([
            'jogo_id' => $jogo->id,
            'player_id' => "player-{$suffix}",
            'short_name' => $playerName,
            'long_name' => "{$playerName} Long",
            'player_positions' => 'CM',
            'overall' => 84,
            'value_eur' => 12500000,
            'wage_eur' => 150000,
            'age' => 24,
            'weak_foot' => 4,
            'skill_moves' => 4,
            'player_traits' => 'Incisive Pass',
            'pace' => 78,
            'shooting' => 75,
            'passing' => 81,
            'dribbling' => 82,
            'defending' => 68,
            'physic' => 74,
        ]);

        $transfer = LigaTransferencia::create(array_merge([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'liga_origem_id' => $liga->id,
            'liga_destino_id' => $liga->id,
            'elencopadrao_id' => $player->id,
            'clube_origem_id' => null,
            'clube_destino_id' => null,
            'tipo' => 'jogador_livre',
            'valor' => 9000000,
            'observacao' => 'Jogador livre adquirido no mercado.',
        ], $overrides));

        DB::table('liga_transferencias')
            ->where('id', $transfer->id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        return $transfer->fresh();
    }
}
