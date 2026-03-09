<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubeFinanceiroMovimento;
use Illuminate\Support\Facades\DB;

class LeagueFinanceService
{
    public function initClubWallet(int $ligaId, int $clubeId): LigaClubeFinanceiro
    {
        return DB::transaction(function () use ($ligaId, $clubeId): LigaClubeFinanceiro {
            $liga = Liga::query()
                ->select(['id', 'saldo_inicial'])
                ->findOrFail($ligaId);

            $wallet = LigaClubeFinanceiro::query()->firstOrCreate(
                [
                    'liga_id' => $ligaId,
                    'clube_id' => $clubeId,
                ],
                [
                    'saldo' => (int) $liga->saldo_inicial,
                ],
            );

            if ($wallet->wasRecentlyCreated) {
                $saldoInicial = (int) $wallet->saldo;

                $this->appendMovimento(
                    ligaId: $ligaId,
                    clubeId: $clubeId,
                    operacao: LigaClubeFinanceiroMovimento::OPERATION_SNAPSHOT_OPENING,
                    descricao: 'Saldo inicial do clube',
                    valor: $saldoInicial,
                    saldoAntes: 0,
                    saldoDepois: $saldoInicial,
                    metadata: [
                        'source' => 'wallet_creation',
                    ],
                );
            }

            return $wallet;
        }, 3);
    }

    public function getSaldo(int $ligaId, int $clubeId): int
    {
        $wallet = $this->initClubWallet($ligaId, $clubeId);

        return (int) $wallet->saldo;
    }

    public function credit(int $ligaId, int $clubeId, int $amount, string $reason = ''): int
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount deve ser >= 0.');
        }

        return DB::transaction(function () use ($ligaId, $clubeId, $amount, $reason): int {
            $wallet = LigaClubeFinanceiro::query()
                ->where('liga_id', $ligaId)
                ->where('clube_id', $clubeId)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $this->initClubWallet($ligaId, $clubeId);
                $wallet->refresh();
            }

            if ($amount === 0) {
                return (int) $wallet->saldo;
            }

            $saldoAntes = (int) $wallet->saldo;
            $saldoDepois = $saldoAntes + $amount;
            $wallet->saldo = $saldoDepois;
            $wallet->save();

            $this->appendMovimento(
                ligaId: $ligaId,
                clubeId: $clubeId,
                operacao: LigaClubeFinanceiroMovimento::OPERATION_CREDIT,
                descricao: $reason !== '' ? $reason : 'Crédito financeiro',
                valor: $amount,
                saldoAntes: $saldoAntes,
                saldoDepois: $saldoDepois,
            );

            return $saldoDepois;
        }, 3);
    }

    public function debit(int $ligaId, int $clubeId, int $amount, string $reason = '', bool $allowNegative = false): int
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount deve ser >= 0.');
        }

        return DB::transaction(function () use ($ligaId, $clubeId, $amount, $reason, $allowNegative): int {
            $wallet = LigaClubeFinanceiro::query()
                ->where('liga_id', $ligaId)
                ->where('clube_id', $clubeId)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $this->initClubWallet($ligaId, $clubeId);
                $wallet->refresh();
            }

            $saldoAtual = (int) $wallet->saldo;

            if ($amount === 0) {
                return $saldoAtual;
            }

            $novoSaldo = $saldoAtual - $amount;

            if (! $allowNegative && $novoSaldo < 0) {
                throw new \DomainException('Saldo insuficiente para completar a operação.');
            }

            $wallet->saldo = $novoSaldo;
            $wallet->save();

            $this->appendMovimento(
                ligaId: $ligaId,
                clubeId: $clubeId,
                operacao: LigaClubeFinanceiroMovimento::OPERATION_DEBIT,
                descricao: $reason !== '' ? $reason : 'Débito financeiro',
                valor: $amount,
                saldoAntes: $saldoAtual,
                saldoDepois: $novoSaldo,
            );

            return (int) $wallet->saldo;
        }, 3);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function appendMovimento(
        int $ligaId,
        int $clubeId,
        string $operacao,
        string $descricao,
        int $valor,
        int $saldoAntes,
        int $saldoDepois,
        array $metadata = [],
    ): void {
        LigaClubeFinanceiroMovimento::query()->create([
            'liga_id' => $ligaId,
            'clube_id' => $clubeId,
            'operacao' => $operacao,
            'descricao' => trim($descricao) !== '' ? trim($descricao) : null,
            'valor' => $valor,
            'saldo_antes' => $saldoAntes,
            'saldo_depois' => $saldoDepois,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }
}
