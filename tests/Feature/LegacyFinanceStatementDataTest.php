<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\LeagueFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyFinanceStatementDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_finance_data_returns_only_three_recent_ledger_entries(): void
    {
        $context = $this->createLeagueContext();
        $user = User::factory()->create();
        $club = $this->createClub($context['liga'], $context['confederacao'], $user);

        /** @var LeagueFinanceService $service */
        $service = app(LeagueFinanceService::class);
        $service->initClubWallet($context['liga']->id, $club->id);
        $service->credit($context['liga']->id, $club->id, 100000, 'Entrada A');
        $service->debit($context['liga']->id, $club->id, 50000, 'Saída B');
        $service->credit($context['liga']->id, $club->id, 70000, 'Entrada C');
        $service->debit($context['liga']->id, $club->id, 20000, 'Saída D');

        $response = $this->actingAs($user)->get(route('legacy.finance.data', [
            'confederacao_id' => $context['confederacao']->id,
        ]));

        $response->assertOk();

        $movimentos = $response->json('financeiro.movimentos');
        $this->assertIsArray($movimentos);
        $this->assertCount(3, $movimentos);
        $this->assertSame('Saída D', $movimentos[0]['descricao'] ?? null);
        $this->assertSame('Entrada C', $movimentos[1]['descricao'] ?? null);
        $this->assertSame('Saída B', $movimentos[2]['descricao'] ?? null);
        $this->assertNotEmpty($response->json('statement.ledger_activated_at'));
    }

    public function test_finance_statement_endpoint_returns_paginated_ledger(): void
    {
        $context = $this->createLeagueContext();
        $user = User::factory()->create();
        $club = $this->createClub($context['liga'], $context['confederacao'], $user);

        /** @var LeagueFinanceService $service */
        $service = app(LeagueFinanceService::class);
        $service->initClubWallet($context['liga']->id, $club->id);
        $service->credit($context['liga']->id, $club->id, 100000, 'Entrada A');
        $service->debit($context['liga']->id, $club->id, 50000, 'Saída B');
        $service->credit($context['liga']->id, $club->id, 70000, 'Entrada C');
        $service->debit($context['liga']->id, $club->id, 20000, 'Saída D');

        $response = $this->actingAs($user)->get(route('legacy.finance.statement.data', [
            'confederacao_id' => $context['confederacao']->id,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertOk();
        $response->assertJsonPath('statement.pagination.page', 1);
        $response->assertJsonPath('statement.pagination.per_page', 2);
        $response->assertJsonPath('statement.pagination.total', 5);
        $response->assertJsonPath('statement.pagination.has_more', true);

        $items = $response->json('statement.items');
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $this->assertSame('Saída D', $items[0]['descricao'] ?? null);
        $this->assertSame('Entrada C', $items[1]['descricao'] ?? null);
    }

    /**
     * @return array{liga:Liga, confederacao:Confederacao}
     */
    private function createLeagueContext(): array
    {
        $suffix = Str::lower(Str::random(6));

        $plataforma = Plataforma::create([
            'nome' => "Plataforma {$suffix}",
            'slug' => "plat-{$suffix}",
        ]);

        $jogo = Jogo::create([
            'nome' => "Jogo {$suffix}",
            'slug' => "jogo-{$suffix}",
        ]);

        $geracao = Geracao::create([
            'nome' => "Geração {$suffix}",
            'slug' => "geracao-{$suffix}",
        ]);

        $confederacao = Confederacao::create([
            'nome' => "Confederação {$suffix}",
            'jogo_id' => $jogo->id,
            'geracao_id' => $geracao->id,
            'plataforma_id' => $plataforma->id,
            'timezone' => 'America/Sao_Paulo',
            'ganho_vitoria_partida' => 750000,
            'ganho_empate_partida' => 300000,
            'ganho_derrota_partida' => 50000,
        ]);

        $liga = Liga::create([
            'nome' => "Liga {$suffix}",
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
        ];
    }

    private function createClub(Liga $liga, Confederacao $confederacao, User $user): LigaClube
    {
        $user->ligas()->attach($liga->id);

        return LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Clube extrato',
        ]);
    }
}
