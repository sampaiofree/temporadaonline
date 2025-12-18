<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClubeFinanceiro;
use Illuminate\Support\Facades\DB;

class LeagueFinanceService
{
    public function initClubWallet(int $ligaId, int $clubeId): LigaClubeFinanceiro
    {
        return DB::transaction(function () use ($ligaId, $clubeId): LigaClubeFinanceiro {
            $liga = Liga::query()
                ->select(['id', 'saldo_inicial'])
                ->findOrFail($ligaId);

            return LigaClubeFinanceiro::query()->firstOrCreate(
                [
                    'liga_id' => $ligaId,
                    'clube_id' => $clubeId,
                ],
                [
                    'saldo' => (int) $liga->saldo_inicial,
                ],
            );
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

        return DB::transaction(function () use ($ligaId, $clubeId, $amount): int {
            $wallet = LigaClubeFinanceiro::query()
                ->where('liga_id', $ligaId)
                ->where('clube_id', $clubeId)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $this->initClubWallet($ligaId, $clubeId);
                $wallet->refresh();
            }

            $wallet->saldo = (int) $wallet->saldo + $amount;
            $wallet->save();

            return (int) $wallet->saldo;
        }, 3);
    }

    public function debit(int $ligaId, int $clubeId, int $amount, string $reason = '', bool $allowNegative = false): int
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount deve ser >= 0.');
        }

        return DB::transaction(function () use ($ligaId, $clubeId, $amount, $allowNegative): int {
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
            $novoSaldo = $saldoAtual - $amount;

            if (! $allowNegative && $novoSaldo < 0) {
                throw new \DomainException('Saldo insuficiente para completar a operação.');
            }

            $wallet->saldo = $novoSaldo;
            $wallet->save();

            return (int) $wallet->saldo;
        }, 3);
    }
}
