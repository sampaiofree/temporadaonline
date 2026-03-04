<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;

class SalaryReserveGuardService
{
    public function assertReserveDoesNotExceedBalance(
        Liga $liga,
        int $clubeId,
        ?int $confederacaoId = null,
        int $reserveDelta = 0,
        int $balanceDelta = 0,
    ): void {
        $scopeConfederacaoId = $confederacaoId !== null && $confederacaoId > 0
            ? $confederacaoId
            : null;

        $currentReserve = $this->resolveActiveSalaryReserve(
            clubeId: $clubeId,
            ligaId: (int) $liga->id,
            confederacaoId: $scopeConfederacaoId,
        );
        $currentBalance = $this->resolveBalance(
            liga: $liga,
            clubeId: $clubeId,
        );

        $projectedReserve = max(0, $currentReserve + $reserveDelta);
        $projectedBalance = $currentBalance + $balanceDelta;

        if ($projectedReserve <= $projectedBalance) {
            return;
        }

        throw new \DomainException(
            "Operacao bloqueada: a reserva salarial projetada (EUR {$projectedReserve}) nao pode superar o saldo em caixa projetado (EUR {$projectedBalance})."
        );
    }

    private function resolveActiveSalaryReserve(int $clubeId, int $ligaId, ?int $confederacaoId = null): int
    {
        $query = LigaClubeElenco::query()
            ->where('liga_clube_id', $clubeId)
            ->where('ativo', true);

        if ($confederacaoId !== null && $confederacaoId > 0) {
            $query->where('confederacao_id', $confederacaoId);
        } else {
            $query->where('liga_id', $ligaId);
        }

        return (int) $query
            ->lockForUpdate()
            ->pluck('wage_eur')
            ->reduce(fn (int $carry, $value): int => $carry + (int) ($value ?? 0), 0);
    }

    private function resolveBalance(Liga $liga, int $clubeId): int
    {
        $walletSaldo = LigaClubeFinanceiro::query()
            ->where('liga_id', (int) $liga->id)
            ->where('clube_id', $clubeId)
            ->lockForUpdate()
            ->value('saldo');

        if ($walletSaldo !== null) {
            return (int) $walletSaldo;
        }

        return (int) ($liga->saldo_inicial ?? 0);
    }
}
