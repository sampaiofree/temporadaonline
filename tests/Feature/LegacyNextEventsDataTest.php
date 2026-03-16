<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaLeilao;
use App\Models\LigaPeriodo;
use App\Models\LigaRouboMulta;
use App\Models\Plataforma;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyNextEventsDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Carbon::setTestNow(Carbon::create(2026, 3, 16, 12, 0, 0, 'America/Sao_Paulo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_next_events_endpoint_returns_open_and_upcoming_windows_for_selected_confederacao(): void
    {
        ['liga' => $ligaA, 'confederacao' => $confederacaoA] = $this->createLeagueContext('A');
        ['liga' => $ligaB, 'confederacao' => $confederacaoB] = $this->createLeagueContext('B');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach([$ligaA->id, $ligaB->id]);

        LigaPeriodo::create([
            'confederacao_id' => $confederacaoA->id,
            'inicio' => '2026-03-16 09:00:00',
            'fim' => '2026-03-16 18:00:00',
        ]);

        LigaPeriodo::create([
            'confederacao_id' => $confederacaoA->id,
            'inicio' => '2026-03-18 10:00:00',
            'fim' => '2026-03-18 19:00:00',
        ]);

        LigaRouboMulta::create([
            'confederacao_id' => $confederacaoA->id,
            'inicio' => '2026-03-16 11:30:00',
            'fim' => '2026-03-16 13:00:00',
        ]);

        LigaRouboMulta::create([
            'confederacao_id' => $confederacaoA->id,
            'inicio' => '2026-03-17 20:00:00',
            'fim' => '2026-03-17 22:30:00',
        ]);

        LigaLeilao::create([
            'confederacao_id' => $confederacaoA->id,
            'inicio' => '2026-03-20',
            'fim' => '2026-03-22',
        ]);

        LigaPeriodo::create([
            'confederacao_id' => $confederacaoB->id,
            'inicio' => '2026-04-01 08:00:00',
            'fim' => '2026-04-01 12:00:00',
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.next_events.data', [
                'confederacao_id' => $confederacaoA->id,
            ]));

        $response->assertOk();

        $items = collect($response->json('events.items'))->keyBy('id');

        $auction = $items->get('auction');
        $multa = $items->get('multa');
        $market = $items->get('market');

        $this->assertSame('upcoming', $auction['status'] ?? null);
        $this->assertNull($auction['current_window'] ?? null);
        $this->assertSame('20/03/2026', $auction['next_window']['start_label'] ?? null);
        $this->assertSame('22/03/2026', $auction['next_window']['end_label'] ?? null);

        $this->assertSame('open', $multa['status'] ?? null);
        $this->assertSame('16/03/2026 11:30', $multa['current_window']['start_label'] ?? null);
        $this->assertSame('16/03/2026 13:00', $multa['current_window']['end_label'] ?? null);
        $this->assertSame('17/03/2026 20:00', $multa['next_window']['start_label'] ?? null);

        $this->assertSame('open', $market['status'] ?? null);
        $this->assertSame('16/03/2026 09:00', $market['current_window']['start_label'] ?? null);
        $this->assertSame('16/03/2026 18:00', $market['current_window']['end_label'] ?? null);
        $this->assertSame('18/03/2026 10:00', $market['next_window']['start_label'] ?? null);
    }

    public function test_next_events_endpoint_returns_none_when_selected_confederacao_has_no_windows(): void
    {
        ['liga' => $ligaA, 'confederacao' => $confederacaoA] = $this->createLeagueContext('C');
        ['liga' => $ligaB, 'confederacao' => $confederacaoB] = $this->createLeagueContext('D');

        $viewer = User::factory()->create();
        $viewer->ligas()->attach([$ligaA->id, $ligaB->id]);

        LigaPeriodo::create([
            'confederacao_id' => $confederacaoB->id,
            'inicio' => '2026-03-16 09:00:00',
            'fim' => '2026-03-16 18:00:00',
        ]);

        LigaRouboMulta::create([
            'confederacao_id' => $confederacaoB->id,
            'inicio' => '2026-03-16 11:00:00',
            'fim' => '2026-03-16 14:00:00',
        ]);

        LigaLeilao::create([
            'confederacao_id' => $confederacaoB->id,
            'inicio' => '2026-03-18',
            'fim' => '2026-03-19',
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('legacy.next_events.data', [
                'confederacao_id' => $confederacaoA->id,
            ]));

        $response->assertOk();

        $items = collect($response->json('events.items'))->keyBy('id');

        $this->assertCount(3, $items);
        $this->assertSame('none', $items->get('auction')['status'] ?? null);
        $this->assertSame('none', $items->get('multa')['status'] ?? null);
        $this->assertSame('none', $items->get('market')['status'] ?? null);
        $this->assertNull($items->get('auction')['current_window'] ?? null);
        $this->assertNull($items->get('auction')['next_window'] ?? null);
    }

    /**
     * @return array{liga:Liga, confederacao:Confederacao}
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
            'max_times' => 20,
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
        ];
    }
}
