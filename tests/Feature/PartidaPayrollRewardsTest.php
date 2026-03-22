<?php

namespace Tests\Feature;

use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeFinanceiro;
use App\Models\Partida;
use App\Models\PartidaFolhaPagamento;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\PartidaPayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartidaPayrollRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_credits_rewards_on_confirmed_score_and_is_idempotent(): void
    {
        $context = $this->createLeagueContext([
            'ganho_vitoria_partida' => 1100000,
            'ganho_empate_partida' => 450000,
            'ganho_derrota_partida' => 120000,
            'saldo_inicial' => 10000000,
        ]);

        $mandante = $this->createClub($context['liga'], $context['confederacao'], 'Mandante');
        $visitante = $this->createClub($context['liga'], $context['confederacao'], 'Visitante');

        $partida = Partida::create([
            'liga_id' => $context['liga']->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => 'placar_confirmado',
            'placar_mandante' => 3,
            'placar_visitante' => 1,
        ]);

        /** @var PartidaPayrollService $service */
        $service = app(PartidaPayrollService::class);

        $service->chargeIfNeeded($partida);

        $this->assertDatabaseHas('partida_folha_pagamento', [
            'partida_id' => $partida->id,
            'clube_id' => $mandante->id,
            'tipo' => PartidaFolhaPagamento::TYPE_MATCH_WIN_REWARD,
            'total_wage' => 1100000,
        ]);

        $this->assertDatabaseHas('partida_folha_pagamento', [
            'partida_id' => $partida->id,
            'clube_id' => $visitante->id,
            'tipo' => PartidaFolhaPagamento::TYPE_MATCH_LOSS_REWARD,
            'total_wage' => 120000,
        ]);

        $mandanteSaldo = LigaClubeFinanceiro::query()
            ->where('liga_id', $context['liga']->id)
            ->where('clube_id', $mandante->id)
            ->value('saldo');
        $visitanteSaldo = LigaClubeFinanceiro::query()
            ->where('liga_id', $context['liga']->id)
            ->where('clube_id', $visitante->id)
            ->value('saldo');

        $this->assertSame(11100000, (int) $mandanteSaldo);
        $this->assertSame(10120000, (int) $visitanteSaldo);

        $service->chargeIfNeeded($partida->fresh());

        $this->assertSame(
            2,
            PartidaFolhaPagamento::query()
                ->where('partida_id', $partida->id)
                ->count(),
        );

        $this->assertSame(
            11100000,
            (int) LigaClubeFinanceiro::query()
                ->where('liga_id', $context['liga']->id)
                ->where('clube_id', $mandante->id)
                ->value('saldo'),
        );
    }

    public function test_it_applies_wo_as_win_and_loss_rewards(): void
    {
        $context = $this->createLeagueContext([
            'ganho_vitoria_partida' => 700000,
            'ganho_empate_partida' => 300000,
            'ganho_derrota_partida' => 90000,
            'saldo_inicial' => 5000000,
        ]);

        $mandante = $this->createClub($context['liga'], $context['confederacao'], 'Mandante WO');
        $visitante = $this->createClub($context['liga'], $context['confederacao'], 'Visitante WO');

        $partida = Partida::create([
            'liga_id' => $context['liga']->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'estado' => 'wo',
            'wo_para_user_id' => $visitante->user_id,
            'placar_mandante' => 0,
            'placar_visitante' => 3,
        ]);

        /** @var PartidaPayrollService $service */
        $service = app(PartidaPayrollService::class);
        $service->chargeIfNeeded($partida);

        $this->assertDatabaseHas('partida_folha_pagamento', [
            'partida_id' => $partida->id,
            'clube_id' => $visitante->id,
            'tipo' => PartidaFolhaPagamento::TYPE_MATCH_WIN_REWARD,
            'total_wage' => 700000,
        ]);

        $this->assertDatabaseHas('partida_folha_pagamento', [
            'partida_id' => $partida->id,
            'clube_id' => $mandante->id,
            'tipo' => PartidaFolhaPagamento::TYPE_MATCH_LOSS_REWARD,
            'total_wage' => 90000,
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
            'ganho_vitoria_partida' => $overrides['ganho_vitoria_partida'] ?? 750000,
            'ganho_empate_partida' => $overrides['ganho_empate_partida'] ?? 300000,
            'ganho_derrota_partida' => $overrides['ganho_derrota_partida'] ?? 50000,
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

    private function createClub(Liga $liga, Confederacao $confederacao, string $nome): LigaClube
    {
        $user = User::factory()->create();
        $user->ligas()->attach($liga->id);

        return LigaClube::create([
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacao->id,
            'user_id' => $user->id,
            'nome' => $nome,
        ]);
    }
}
