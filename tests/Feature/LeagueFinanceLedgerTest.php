<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubeFinanceiroMovimento;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\LeagueFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeagueFinanceLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_init_wallet_creates_single_opening_snapshot(): void
    {
        $context = $this->createLeagueContext([
            'saldo_inicial' => 1500000,
        ]);
        $club = $this->createClub($context['liga'], $context['confederacao']);

        /** @var LeagueFinanceService $service */
        $service = app(LeagueFinanceService::class);

        $wallet = $service->initClubWallet($context['liga']->id, $club->id);
        $this->assertSame(1500000, (int) $wallet->saldo);

        $this->assertDatabaseHas('liga_clube_financeiro_movimentos', [
            'liga_id' => $context['liga']->id,
            'clube_id' => $club->id,
            'operacao' => LigaClubeFinanceiroMovimento::OPERATION_SNAPSHOT_OPENING,
            'descricao' => 'Saldo inicial do clube',
            'valor' => 1500000,
            'saldo_antes' => 0,
            'saldo_depois' => 1500000,
        ]);

        $service->initClubWallet($context['liga']->id, $club->id);

        $this->assertSame(
            1,
            LigaClubeFinanceiroMovimento::query()
                ->where('liga_id', $context['liga']->id)
                ->where('clube_id', $club->id)
                ->where('operacao', LigaClubeFinanceiroMovimento::OPERATION_SNAPSHOT_OPENING)
                ->count(),
        );
    }

    public function test_credit_and_debit_write_ledger_with_balances(): void
    {
        $context = $this->createLeagueContext([
            'saldo_inicial' => 1000000,
        ]);
        $club = $this->createClub($context['liga'], $context['confederacao']);

        /** @var LeagueFinanceService $service */
        $service = app(LeagueFinanceService::class);
        $service->initClubWallet($context['liga']->id, $club->id);

        $afterCredit = $service->credit($context['liga']->id, $club->id, 500000, 'Patrocínio testado');
        $this->assertSame(1500000, $afterCredit);

        $afterDebit = $service->debit($context['liga']->id, $club->id, 200000, 'Compra de jogador teste');
        $this->assertSame(1300000, $afterDebit);

        $this->assertDatabaseHas('liga_clube_financeiro', [
            'liga_id' => $context['liga']->id,
            'clube_id' => $club->id,
            'saldo' => 1300000,
        ]);

        $this->assertDatabaseHas('liga_clube_financeiro_movimentos', [
            'liga_id' => $context['liga']->id,
            'clube_id' => $club->id,
            'operacao' => LigaClubeFinanceiroMovimento::OPERATION_CREDIT,
            'descricao' => 'Patrocínio testado',
            'valor' => 500000,
            'saldo_antes' => 1000000,
            'saldo_depois' => 1500000,
        ]);

        $this->assertDatabaseHas('liga_clube_financeiro_movimentos', [
            'liga_id' => $context['liga']->id,
            'clube_id' => $club->id,
            'operacao' => LigaClubeFinanceiroMovimento::OPERATION_DEBIT,
            'descricao' => 'Compra de jogador teste',
            'valor' => 200000,
            'saldo_antes' => 1500000,
            'saldo_depois' => 1300000,
        ]);
    }

    /**
     * @param array<string, int> $overrides
     * @return array{liga:Liga, confederacao:Confederacao}
     */
    private function createLeagueContext(array $overrides = []): array
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
            'saldo_inicial' => $overrides['saldo_inicial'] ?? 1000000,
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

    private function createClub(Liga $liga, Confederacao $confederacao): LigaClube
    {
        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        return LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => 'Clube teste',
        ]);
    }
}
