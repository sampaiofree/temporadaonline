<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaLeilao;
use App\Models\Plataforma;
use App\Services\MarketWindowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketWindowServiceAuctionPeriodsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_market_window_service_marks_auction_active_only_inside_exact_time_range(): void
    {
        $liga = $this->createLigaContext('active-range');

        LigaLeilao::create([
            'confederacao_id' => $liga->confederacao_id,
            'inicio' => '2026-03-20 09:00:00',
            'fim' => '2026-03-20 18:00:00',
        ]);

        $service = app(MarketWindowService::class);

        Carbon::setTestNow(Carbon::create(2026, 3, 20, 8, 59, 59, 'America/Sao_Paulo'));
        $beforeStart = $service->resolveForLiga($liga);
        $this->assertFalse($beforeStart['is_auction']);
        $this->assertNull($beforeStart['auction_period']);

        Carbon::setTestNow(Carbon::create(2026, 3, 20, 9, 0, 0, 'America/Sao_Paulo'));
        $atStart = $service->resolveForLiga($liga);
        $this->assertTrue($atStart['is_auction']);
        $this->assertSame('2026-03-20 09:00:00', $atStart['auction_period']['inicio'] ?? null);
        $this->assertSame('2026-03-20 18:00:00', $atStart['auction_period']['fim'] ?? null);

        Carbon::setTestNow(Carbon::create(2026, 3, 20, 18, 0, 1, 'America/Sao_Paulo'));
        $afterEnd = $service->resolveForLiga($liga);
        $this->assertFalse($afterEnd['is_auction']);
        $this->assertNull($afterEnd['auction_period']);
    }

    public function test_market_window_service_returns_next_auction_period_with_datetime_precision(): void
    {
        $liga = $this->createLigaContext('next-range');

        LigaLeilao::create([
            'confederacao_id' => $liga->confederacao_id,
            'inicio' => '2026-03-20 09:00:00',
            'fim' => '2026-03-20 10:00:00',
        ]);

        LigaLeilao::create([
            'confederacao_id' => $liga->confederacao_id,
            'inicio' => '2026-03-20 19:30:00',
            'fim' => '2026-03-20 21:00:00',
        ]);

        LigaLeilao::create([
            'confederacao_id' => $liga->confederacao_id,
            'inicio' => '2026-03-22 08:00:00',
            'fim' => '2026-03-22 12:00:00',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 3, 20, 12, 0, 0, 'America/Sao_Paulo'));

        $window = app(MarketWindowService::class)->resolveForLiga($liga);

        $this->assertFalse($window['is_auction']);
        $this->assertNull($window['auction_period']);
        $this->assertSame('2026-03-20 19:30:00', $window['next_auction_period']['inicio'] ?? null);
        $this->assertSame('2026-03-20 21:00:00', $window['next_auction_period']['fim'] ?? null);
        $this->assertSame('20/03/2026 19:30', $window['next_auction_period']['inicio_label'] ?? null);
        $this->assertSame('20/03/2026 21:00', $window['next_auction_period']['fim_label'] ?? null);
    }

    private function createLigaContext(string $suffix): Liga
    {
        $unique = Str::lower(Str::random(6)).'-'.$suffix;

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

        return Liga::create([
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
    }
}
