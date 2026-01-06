<?php

namespace App\Services;

use App\Models\LigaClubeElenco;
use App\Models\Partida;
use Illuminate\Support\Facades\DB;

class PartidaPayrollService
{
    private const WO_FINE_MULTIPLIER = 0.2;

    public function __construct(private readonly LeagueFinanceService $finance)
    {
    }

    public function chargeIfNeeded(Partida $partida): void
    {
        if (! in_array($partida->estado, ['placar_confirmado', 'wo'], true)) {
            return;
        }

        $clubIds = array_values(array_filter([
            $partida->mandante_id,
            $partida->visitante_id,
        ]));

        if (count($clubIds) !== 2) {
            return;
        }

        $wagesByClub = LigaClubeElenco::query()
            ->selectRaw('liga_clube_id, SUM(wage_eur) as total_wage')
            ->where('liga_id', $partida->liga_id)
            ->whereIn('liga_clube_id', $clubIds)
            ->where('ativo', true)
            ->groupBy('liga_clube_id')
            ->pluck('total_wage', 'liga_clube_id');

        $penalizedClubId = $partida->estado === 'wo' ? $this->resolveWoPenalizedClubId($partida) : null;
        $now = now();

        foreach ($clubIds as $clubId) {
            $totalWage = (int) ($wagesByClub[$clubId] ?? 0);
            $multaWo = $penalizedClubId && (int) $penalizedClubId === (int) $clubId
                ? (int) round($totalWage * self::WO_FINE_MULTIPLIER)
                : 0;

            DB::transaction(function () use ($partida, $clubId, $totalWage, $multaWo, $now): void {
                $inserted = DB::table('partida_folha_pagamento')->insertOrIgnore([
                    'liga_id' => $partida->liga_id,
                    'partida_id' => $partida->id,
                    'clube_id' => $clubId,
                    'total_wage' => $totalWage,
                    'multa_wo' => $multaWo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($inserted === 0) {
                    return;
                }

                $this->finance->debit(
                    $partida->liga_id,
                    $clubId,
                    $totalWage + $multaWo,
                    "Cobrança de salário da partida {$partida->id}",
                    allowNegative: true,
                );
            }, 3);
        }
    }

    private function resolveWoPenalizedClubId(Partida $partida): ?int
    {
        $partida->loadMissing(['mandante.user', 'visitante.user']);

        $winnerUserId = $partida->wo_para_user_id;
        $mandanteUserId = $partida->mandante?->user_id;
        $visitanteUserId = $partida->visitante?->user_id;

        if (! $winnerUserId || ! $mandanteUserId || ! $visitanteUserId) {
            return null;
        }

        if ((int) $winnerUserId === (int) $mandanteUserId) {
            return $partida->visitante_id;
        }

        if ((int) $winnerUserId === (int) $visitanteUserId) {
            return $partida->mandante_id;
        }

        return null;
    }
}
