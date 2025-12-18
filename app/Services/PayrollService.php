<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(private readonly LeagueFinanceService $finance)
    {
    }

    public function chargeRound(int $ligaId, int $rodadaNumero): array
    {
        $liga = Liga::query()->findOrFail($ligaId);

        if ($liga->cobranca_salario !== 'rodada') {
            throw new \DomainException('Configuração de cobrança de salário não suportada.');
        }

        $clubes = LigaClube::query()
            ->where('liga_id', $ligaId)
            ->get(['id']);

        $resultados = [];

        foreach ($clubes as $clube) {
            $resultados[] = $this->chargeClub($ligaId, (int) $clube->id, $rodadaNumero);
        }

        return $resultados;
    }

    private function chargeClub(int $ligaId, int $clubeId, int $rodadaNumero): array
    {
        return DB::transaction(function () use ($ligaId, $clubeId, $rodadaNumero): array {
            $totalWage = (int) LigaClubeElenco::query()
                ->where('liga_id', $ligaId)
                ->where('liga_clube_id', $clubeId)
                ->where('ativo', true)
                ->sum('wage_eur');

            $now = now();

            $inserted = DB::table('liga_folha_pagamento')->insertOrIgnore([
                'liga_id' => $ligaId,
                'rodada' => $rodadaNumero,
                'clube_id' => $clubeId,
                'total_wage' => $totalWage,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 0) {
                return [
                    'clube_id' => $clubeId,
                    'rodada' => $rodadaNumero,
                    'total_wage' => $totalWage,
                    'charged' => false,
                ];
            }

            $saldo = $this->finance->debit(
                $ligaId,
                $clubeId,
                $totalWage,
                "Cobrança de salário da rodada {$rodadaNumero}",
                allowNegative: true,
            );

            return [
                'clube_id' => $clubeId,
                'rodada' => $rodadaNumero,
                'total_wage' => $totalWage,
                'saldo' => $saldo,
                'charged' => true,
            ];
        }, 3);
    }
}

