<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubeFinanceiroMovimento;
use Illuminate\Support\Facades\DB;

class LeagueFinanceService
{
    public const EVENT_MARKET_BUY_FREE = 'market_buy_free';
    public const EVENT_MARKET_SELL_RELEASE = 'market_sell_release';
    public const EVENT_TRANSFER_BUY = 'transfer_buy';
    public const EVENT_TRANSFER_SELL = 'transfer_sell';
    public const EVENT_RELEASE_CLAUSE_PAID = 'release_clause_paid';
    public const EVENT_RELEASE_CLAUSE_RECEIVED = 'release_clause_received';
    public const EVENT_TRADE_ADJUSTMENT_PAID = 'trade_adjustment_paid';
    public const EVENT_TRADE_ADJUSTMENT_RECEIVED = 'trade_adjustment_received';
    public const EVENT_PROPOSAL_PAID = 'proposal_paid';
    public const EVENT_PROPOSAL_RECEIVED = 'proposal_received';
    public const EVENT_AUCTION_BID = 'auction_bid';
    public const EVENT_AUCTION_REFUND_OUTBID = 'auction_refund_outbid';
    public const EVENT_AUCTION_REFUND_CANCELLED = 'auction_refund_cancelled';
    public const EVENT_MATCH_REWARD = 'match_reward';
    public const EVENT_SPONSORSHIP_CLAIM = 'sponsorship_claim';
    public const EVENT_ROUND_PAYROLL_LEGACY = 'round_payroll_legacy';

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

    /**
     * @param array<string, mixed> $metadata
     */
    public function credit(int $ligaId, int $clubeId, int $amount, string $reason = '', array $metadata = []): int
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount deve ser >= 0.');
        }

        return DB::transaction(function () use ($ligaId, $clubeId, $amount, $reason, $metadata): int {
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

            $descricao = $this->resolveDescricao(
                LigaClubeFinanceiroMovimento::OPERATION_CREDIT,
                $amount,
                $reason,
                $metadata,
            );

            $this->appendMovimento(
                ligaId: $ligaId,
                clubeId: $clubeId,
                operacao: LigaClubeFinanceiroMovimento::OPERATION_CREDIT,
                descricao: $descricao,
                valor: $amount,
                saldoAntes: $saldoAntes,
                saldoDepois: $saldoDepois,
                metadata: $metadata,
            );

            return $saldoDepois;
        }, 3);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function debit(
        int $ligaId,
        int $clubeId,
        int $amount,
        string $reason = '',
        bool $allowNegative = false,
        array $metadata = [],
    ): int
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount deve ser >= 0.');
        }

        return DB::transaction(function () use ($ligaId, $clubeId, $amount, $reason, $allowNegative, $metadata): int {
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

            $descricao = $this->resolveDescricao(
                LigaClubeFinanceiroMovimento::OPERATION_DEBIT,
                $amount,
                $reason,
                $metadata,
            );

            $this->appendMovimento(
                ligaId: $ligaId,
                clubeId: $clubeId,
                operacao: LigaClubeFinanceiroMovimento::OPERATION_DEBIT,
                descricao: $descricao,
                valor: $amount,
                saldoAntes: $saldoAtual,
                saldoDepois: $novoSaldo,
                metadata: $metadata,
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

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolveDescricao(string $operacao, int $amount, string $reason, array $metadata): string
    {
        $eventKey = $this->readString($metadata, 'event_key', '');

        $descricao = match ($eventKey) {
            self::EVENT_MARKET_BUY_FREE => $this->describeMarketBuyFree($metadata, $amount),
            self::EVENT_MARKET_SELL_RELEASE => $this->describeMarketSellRelease($metadata, $amount),
            self::EVENT_TRANSFER_BUY => $this->describeTransferBuy($metadata, $amount),
            self::EVENT_TRANSFER_SELL => $this->describeTransferSell($metadata, $amount),
            self::EVENT_RELEASE_CLAUSE_PAID => $this->describeReleaseClausePaid($metadata, $amount),
            self::EVENT_RELEASE_CLAUSE_RECEIVED => $this->describeReleaseClauseReceived($metadata, $amount),
            self::EVENT_TRADE_ADJUSTMENT_PAID => $this->describeTradeAdjustmentPaid($metadata, $amount),
            self::EVENT_TRADE_ADJUSTMENT_RECEIVED => $this->describeTradeAdjustmentReceived($metadata, $amount),
            self::EVENT_PROPOSAL_PAID => $this->describeProposalPaid($metadata, $amount),
            self::EVENT_PROPOSAL_RECEIVED => $this->describeProposalReceived($metadata, $amount),
            self::EVENT_AUCTION_BID => $this->describeAuctionBid($metadata, $amount),
            self::EVENT_AUCTION_REFUND_OUTBID => $this->describeAuctionRefundOutbid($metadata, $amount),
            self::EVENT_AUCTION_REFUND_CANCELLED => $this->describeAuctionRefundCancelled($metadata, $amount),
            self::EVENT_MATCH_REWARD => $this->describeMatchReward($metadata, $amount),
            self::EVENT_SPONSORSHIP_CLAIM => $this->describeSponsorshipClaim($metadata, $amount),
            self::EVENT_ROUND_PAYROLL_LEGACY => $this->describeRoundPayrollLegacy($metadata, $amount),
            default => trim($reason) !== ''
                ? trim($reason)
                : ($operacao === LigaClubeFinanceiroMovimento::OPERATION_DEBIT ? 'Debito financeiro' : 'Credito financeiro'),
        };

        return $this->limitDescricao($descricao);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeMarketBuyFree(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $salary = $this->readInt($metadata, 'salary_value', 0);
        $total = $this->readInt($metadata, 'total_value', $amount);

        return sprintf(
            'COMPRA LIVRE: %s | Salario M$ %s | Total M$ %s',
            $playerName,
            $this->formatMoney($salary),
            $this->formatMoney($total),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeMarketSellRelease(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $taxPercent = $this->readInt($metadata, 'tax_percent', 0);

        if ($taxPercent > 0) {
            return sprintf(
                'VENDA AO MERCADO: %s | Credito M$ %s | Taxa %d%%',
                $playerName,
                $this->formatMoney($amount),
                $taxPercent,
            );
        }

        return sprintf(
            'VENDA AO MERCADO: %s | Credito M$ %s',
            $playerName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeTransferBuy(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $fromClubName = $this->readString($metadata, 'from_club_name', 'Clube de origem');

        return sprintf(
            'COMPRA NEGOCIADA: %s (%s) | M$ %s',
            $playerName,
            $fromClubName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeTransferSell(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $toClubName = $this->readString($metadata, 'to_club_name', 'Clube de destino');

        return sprintf(
            'VENDA NEGOCIADA: %s (%s) | M$ %s',
            $playerName,
            $toClubName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeReleaseClausePaid(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $fromClubName = $this->readString($metadata, 'from_club_name', 'Clube de origem');

        return sprintf(
            'MULTA PAGA: %s (%s) | M$ %s',
            $playerName,
            $fromClubName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeReleaseClauseReceived(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $toClubName = $this->readString($metadata, 'to_club_name', 'Clube de destino');

        return sprintf(
            'MULTA RECEBIDA: %s (%s) | M$ %s',
            $playerName,
            $toClubName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeTradeAdjustmentPaid(array $metadata, int $amount): string
    {
        $counterClubName = $this->readString($metadata, 'counter_club_name', 'Outro clube');

        return sprintf(
            'AJUSTE DE TROCA PAGO: %s | M$ %s',
            $counterClubName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeTradeAdjustmentReceived(array $metadata, int $amount): string
    {
        $counterClubName = $this->readString($metadata, 'counter_club_name', 'Outro clube');

        return sprintf(
            'AJUSTE DE TROCA RECEBIDO: %s | M$ %s',
            $counterClubName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeProposalPaid(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $proposalId = $this->readInt($metadata, 'proposal_id', 0);

        if ($proposalId > 0) {
            return sprintf(
                'PROPOSTA ACEITA (PAGO): %s | M$ %s | Proposta #%d',
                $playerName,
                $this->formatMoney($amount),
                $proposalId,
            );
        }

        return sprintf(
            'PROPOSTA ACEITA (PAGO): %s | M$ %s',
            $playerName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeProposalReceived(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');
        $proposalId = $this->readInt($metadata, 'proposal_id', 0);

        if ($proposalId > 0) {
            return sprintf(
                'PROPOSTA ACEITA (RECEBIDO): %s | M$ %s | Proposta #%d',
                $playerName,
                $this->formatMoney($amount),
                $proposalId,
            );
        }

        return sprintf(
            'PROPOSTA ACEITA (RECEBIDO): %s | M$ %s',
            $playerName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeAuctionBid(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');

        return sprintf(
            'LANCE EM LEILAO: %s | M$ %s',
            $playerName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeAuctionRefundOutbid(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');

        return sprintf(
            'ESTORNO DE LANCE SUPERADO: %s | M$ %s',
            $playerName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeAuctionRefundCancelled(array $metadata, int $amount): string
    {
        $playerName = $this->readString($metadata, 'player_name', 'Jogador');

        return sprintf(
            'ESTORNO DE LEILAO CANCELADO: %s | M$ %s',
            $playerName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeMatchReward(array $metadata, int $amount): string
    {
        $result = strtolower($this->readString($metadata, 'match_result', 'partida'));
        $resultLabel = match ($result) {
            'vitoria' => 'VITORIA',
            'empate' => 'EMPATE',
            'derrota' => 'DERROTA',
            default => strtoupper($result),
        };
        $opponent = $this->readString($metadata, 'opponent_club_name', 'Adversario');
        $matchId = $this->readInt($metadata, 'match_id', 0);

        if ($matchId > 0) {
            return sprintf(
                'PREMIACAO DE PARTIDA: %s vs %s (#%d) | M$ %s',
                $resultLabel,
                $opponent,
                $matchId,
                $this->formatMoney($amount),
            );
        }

        return sprintf(
            'PREMIACAO DE PARTIDA: %s vs %s | M$ %s',
            $resultLabel,
            $opponent,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeSponsorshipClaim(array $metadata, int $amount): string
    {
        $sponsorshipName = $this->readString($metadata, 'sponsorship_name', 'Patrocinio');

        return sprintf(
            'PATROCINIO RESGATADO: %s | M$ %s',
            $sponsorshipName,
            $this->formatMoney($amount),
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function describeRoundPayrollLegacy(array $metadata, int $amount): string
    {
        $round = $this->readInt($metadata, 'round', 0);

        if ($round > 0) {
            return sprintf(
                'FOLHA SALARIAL (LEGADO): Rodada %d | M$ %s',
                $round,
                $this->formatMoney($amount),
            );
        }

        return sprintf(
            'FOLHA SALARIAL (LEGADO): M$ %s',
            $this->formatMoney($amount),
        );
    }

    private function formatMoney(int $amount): string
    {
        return number_format($amount / 1000000, 2, ',', '.');
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function readString(array $metadata, string $key, string $fallback = ''): string
    {
        $value = $metadata[$key] ?? null;
        if (! is_scalar($value)) {
            return $fallback;
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function readInt(array $metadata, string $key, int $fallback = 0): int
    {
        $value = $metadata[$key] ?? null;

        if (! is_numeric($value)) {
            return $fallback;
        }

        return (int) round((float) $value);
    }

    private function limitDescricao(string $descricao): string
    {
        $singleLine = trim((string) preg_replace('/\s+/', ' ', trim($descricao)));

        if ($singleLine === '') {
            return '';
        }

        if (mb_strlen($singleLine) <= 255) {
            return $singleLine;
        }

        return mb_substr($singleLine, 0, 252).'...';
    }
}
