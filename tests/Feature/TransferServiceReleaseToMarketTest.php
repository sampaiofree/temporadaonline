<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Elencopadrao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubeFinanceiroMovimento;
use App\Models\LigaClubeVendaMercado;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferServiceReleaseToMarketTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_to_market_uses_elencopadrao_value_for_credit(): void
    {
        $plataforma = Plataforma::create([
            'nome' => 'PlayStation 5',
            'slug' => 'ps5-release-market',
        ]);

        $jogo = Jogo::create([
            'nome' => 'FC26',
            'slug' => 'fc26-release-market',
        ]);

        $geracao = Geracao::create([
            'nome' => 'Nova',
            'slug' => 'nova-release-market',
        ]);

        $confederacao = Confederacao::create([
            'nome' => 'Confederacao Release Market',
            'descricao' => 'Confederacao de teste.',
            'timezone' => 'America/Sao_Paulo',
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
        ]);

        $liga = Liga::create([
            'nome' => 'Liga Release Market',
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
        ]);

        $owner = User::factory()->create();

        $clube = LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $owner->id,
            'nome' => 'Clube Owner',
        ]);

        $player = Elencopadrao::create([
            'jogo_id' => $jogo->id,
            'player_id' => 'release-market-player',
            'long_name' => 'Venda Mercado Ajustada',
            'short_name' => 'Venda Ajustada',
            'value_eur' => 200,
            'wage_eur' => 10,
        ]);

        $entry = LigaClubeElenco::create([
            'confederacao_id' => $confederacao->id,
            'liga_id' => $liga->id,
            'liga_clube_id' => $clube->id,
            'elencopadrao_id' => $player->id,
            'value_eur' => 400,
            'wage_eur' => 10,
            'ativo' => true,
        ]);

        $result = app(TransferService::class)->releaseToMarket($entry);

        $wallet = LigaClubeFinanceiro::query()
            ->where('liga_id', $liga->id)
            ->where('clube_id', $clube->id)
            ->firstOrFail();

        $saleRecord = LigaClubeVendaMercado::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $clube->id)
            ->where('elencopadrao_id', $player->id)
            ->firstOrFail();

        $movement = LigaClubeFinanceiroMovimento::query()
            ->where('liga_id', $liga->id)
            ->where('clube_id', $clube->id)
            ->where('operacao', LigaClubeFinanceiroMovimento::OPERATION_CREDIT)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(200, $result['base_value']);
        $this->assertSame(40, $result['tax_value']);
        $this->assertSame(160, $result['credit']);
        $this->assertSame(1160, (int) $wallet->saldo);
        $this->assertSame(200, (int) $saleRecord->valor_base);
        $this->assertSame(160, (int) $saleRecord->valor_credito);
        $this->assertSame(200, (int) data_get($movement->metadata, 'base_value'));
        $this->assertSame(160, (int) data_get($movement->metadata, 'action_value'));
        $this->assertDatabaseMissing('liga_clube_elencos', [
            'id' => $entry->id,
        ]);
    }
}
