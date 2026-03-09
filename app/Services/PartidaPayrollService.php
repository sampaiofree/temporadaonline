<?php

namespace App\Services;

use App\Models\Partida;
use App\Models\PartidaFolhaPagamento;
use Illuminate\Support\Facades\DB;

class PartidaPayrollService
{
    private const DEFAULT_WIN_REWARD = 750000;
    private const DEFAULT_DRAW_REWARD = 300000;
    private const DEFAULT_LOSS_REWARD = 50000;

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

        $rewardsByClub = $this->resolveRewardsByClub($partida, $clubIds);
        $now = now();
        $partida->loadMissing([
            'mandante:id,nome',
            'visitante:id,nome',
        ]);

        foreach ($clubIds as $clubId) {
            $reward = $rewardsByClub[$clubId] ?? null;
            if (! is_array($reward)) {
                continue;
            }

            $tipo = (string) ($reward['tipo'] ?? PartidaFolhaPagamento::TYPE_MATCH_DRAW_REWARD);
            $valor = max(0, (int) ($reward['valor'] ?? 0));

            DB::transaction(function () use ($partida, $clubId, $tipo, $valor, $now): void {
                $inserted = DB::table('partida_folha_pagamento')->insertOrIgnore([
                    'liga_id' => $partida->liga_id,
                    'partida_id' => $partida->id,
                    'clube_id' => $clubId,
                    'tipo' => $tipo,
                    'total_wage' => $valor,
                    'multa_wo' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($inserted === 0) {
                    return;
                }

                if ($valor <= 0) {
                    return;
                }

                $opponentClubName = $this->resolveOpponentClubName($partida, (int) $clubId);
                $resultKey = $this->resolveResultKeyFromTipo($tipo);

                $this->finance->credit(
                    $partida->liga_id,
                    $clubId,
                    $valor,
                    metadata: [
                        'event_key' => LeagueFinanceService::EVENT_MATCH_REWARD,
                        'match_id' => (int) $partida->id,
                        'match_state' => (string) $partida->estado,
                        'match_result' => $resultKey,
                        'opponent_club_name' => $opponentClubName,
                        'action_value' => $valor,
                        'total_value' => $valor,
                    ],
                );
            }, 3);
        }
    }

    /**
     * @param array<int, int> $clubIds
     * @return array<int, array{tipo: string, valor: int}>
     */
    private function resolveRewardsByClub(Partida $partida, array $clubIds): array
    {
        $partida->loadMissing(['liga.confederacao', 'mandante.user', 'visitante.user']);

        $winReward = max(
            0,
            (int) ($partida->liga?->confederacao?->ganho_vitoria_partida ?? self::DEFAULT_WIN_REWARD)
        );
        $drawReward = max(
            0,
            (int) ($partida->liga?->confederacao?->ganho_empate_partida ?? self::DEFAULT_DRAW_REWARD)
        );
        $lossReward = max(
            0,
            (int) ($partida->liga?->confederacao?->ganho_derrota_partida ?? self::DEFAULT_LOSS_REWARD)
        );

        if ($partida->estado === 'wo') {
            $loserClubId = $this->resolveWoPenalizedClubId($partida);
            if (! $loserClubId) {
                return [];
            }

            $winnerClubId = null;
            foreach ($clubIds as $clubId) {
                if ((int) $clubId !== (int) $loserClubId) {
                    $winnerClubId = (int) $clubId;
                    break;
                }
            }

            if (! $winnerClubId) {
                return [];
            }

            return [
                $winnerClubId => [
                    'tipo' => PartidaFolhaPagamento::TYPE_MATCH_WIN_REWARD,
                    'valor' => $winReward,
                ],
                (int) $loserClubId => [
                    'tipo' => PartidaFolhaPagamento::TYPE_MATCH_LOSS_REWARD,
                    'valor' => $lossReward,
                ],
            ];
        }

        $mandanteGoals = (int) ($partida->placar_mandante ?? 0);
        $visitanteGoals = (int) ($partida->placar_visitante ?? 0);
        $mandanteClubId = (int) $partida->mandante_id;
        $visitanteClubId = (int) $partida->visitante_id;

        if ($mandanteGoals === $visitanteGoals) {
            return [
                $mandanteClubId => [
                    'tipo' => PartidaFolhaPagamento::TYPE_MATCH_DRAW_REWARD,
                    'valor' => $drawReward,
                ],
                $visitanteClubId => [
                    'tipo' => PartidaFolhaPagamento::TYPE_MATCH_DRAW_REWARD,
                    'valor' => $drawReward,
                ],
            ];
        }

        if ($mandanteGoals > $visitanteGoals) {
            return [
                $mandanteClubId => [
                    'tipo' => PartidaFolhaPagamento::TYPE_MATCH_WIN_REWARD,
                    'valor' => $winReward,
                ],
                $visitanteClubId => [
                    'tipo' => PartidaFolhaPagamento::TYPE_MATCH_LOSS_REWARD,
                    'valor' => $lossReward,
                ],
            ];
        }

        return [
            $mandanteClubId => [
                'tipo' => PartidaFolhaPagamento::TYPE_MATCH_LOSS_REWARD,
                'valor' => $lossReward,
            ],
            $visitanteClubId => [
                'tipo' => PartidaFolhaPagamento::TYPE_MATCH_WIN_REWARD,
                'valor' => $winReward,
            ],
        ];
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

    private function resolveOpponentClubName(Partida $partida, int $clubId): string
    {
        if ((int) $partida->mandante_id === $clubId) {
            return trim((string) ($partida->visitante?->nome ?? 'Adversario'));
        }

        if ((int) $partida->visitante_id === $clubId) {
            return trim((string) ($partida->mandante?->nome ?? 'Adversario'));
        }

        return 'Adversario';
    }

    private function resolveResultKeyFromTipo(string $tipo): string
    {
        return match ($tipo) {
            PartidaFolhaPagamento::TYPE_MATCH_WIN_REWARD => 'vitoria',
            PartidaFolhaPagamento::TYPE_MATCH_DRAW_REWARD => 'empate',
            PartidaFolhaPagamento::TYPE_MATCH_LOSS_REWARD => 'derrota',
            default => 'partida',
        };
    }
}
