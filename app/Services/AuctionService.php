<?php

namespace App\Services;

use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaLeilaoItem;
use App\Models\LigaLeilaoLance;
use App\Models\LigaTransferencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    public const BID_INCREMENT_OPTIONS = [100_000, 200_000, 300_000, 500_000, 1_000_000];
    public const BID_DURATION_SECONDS = 300;

    public function __construct(
        private readonly LeagueFinanceService $finance,
        private readonly MarketWindowService $marketWindowService,
    ) {
    }

    public function getBidIncrementOptions(): array
    {
        return self::BID_INCREMENT_OPTIONS;
    }

    public function getBidDurationSeconds(): int
    {
        return self::BID_DURATION_SECONDS;
    }

    public function placeBid(Liga $liga, LigaClube $clube, int $elencopadraoId, ?int $increment = null): array
    {
        if (! $liga->confederacao_id) {
            throw new \DomainException('Leilão disponível apenas para ligas com confederação.');
        }

        if (! $this->marketWindowService->isAuctionActive($liga)) {
            throw new \DomainException('Leilão indisponível fora do período de leilão.');
        }

        $this->finalizeExpiredAuctions((int) $liga->confederacao_id);

        return DB::transaction(function () use ($liga, $clube, $elencopadraoId, $increment): array {
            $lockedLiga = Liga::query()
                ->with('confederacao:id,jogo_id')
                ->lockForUpdate()
                ->findOrFail($liga->id);
            $lockedClube = LigaClube::query()->lockForUpdate()->findOrFail($clube->id);

            if ((int) $lockedClube->liga_id !== (int) $lockedLiga->id) {
                throw new \DomainException('Clube não pertence à liga informada.');
            }

            if ((int) ($lockedClube->confederacao_id ?? 0) !== (int) ($lockedLiga->confederacao_id ?? 0)) {
                throw new \DomainException('Clube não pertence à confederação ativa.');
            }

            $player = Elencopadrao::query()->lockForUpdate()->findOrFail($elencopadraoId);
            $marketJogoId = (int) ($lockedLiga->confederacao?->jogo_id ?? $lockedLiga->jogo_id);
            if ((int) $player->jogo_id !== $marketJogoId) {
                throw new \DomainException('Jogador não pertence ao jogo desta liga.');
            }

            if ($this->isPlayerTakenInScope($lockedLiga, $elencopadraoId)) {
                throw new \DomainException('Jogador não está disponível no leilão.');
            }

            $this->assertRosterLimit($lockedLiga, (int) $lockedClube->id, (int) $lockedClube->liga_id);

            $now = Carbon::now('UTC');
            $marketValue = max(0, (int) ($player->value_eur ?? 0));
            $baseValue = $this->resolveInitialBidValue($marketValue);

            $item = LigaLeilaoItem::query()
                ->where('confederacao_id', $lockedLiga->confederacao_id)
                ->where('elencopadrao_id', $elencopadraoId)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                $item = LigaLeilaoItem::create([
                    'confederacao_id' => $lockedLiga->confederacao_id,
                    'elencopadrao_id' => $elencopadraoId,
                    'valor_inicial' => $baseValue,
                    'status' => 'aberto',
                ]);

                $item->refresh();
            } elseif ($item->status !== 'aberto') {
                $item->status = 'aberto';
                $item->valor_inicial = $baseValue;
                $item->valor_atual = null;
                $item->clube_lider_id = null;
                $item->expira_em = null;
                $item->motivo_cancelamento = null;
                $item->finalized_at = null;
                $item->save();
            }

            $hasLeader = $item->clube_lider_id
                && $item->expira_em
                && $item->expira_em->gt($now)
                && $item->status === 'aberto'
                && (int) ($item->valor_atual ?? 0) > 0;

            if ($hasLeader && (int) $item->clube_lider_id === (int) $lockedClube->id) {
                throw new \DomainException('Seu clube já lidera este leilão.');
            }

            $previousLeaderId = null;
            $previousValue = 0;

            if (! $hasLeader) {
                $newValue = $baseValue;
            } else {
                $step = (int) ($increment ?? min(self::BID_INCREMENT_OPTIONS));
                if (! in_array($step, self::BID_INCREMENT_OPTIONS, true)) {
                    throw new \DomainException('Incremento de lance inválido.');
                }

                $previousLeaderId = (int) $item->clube_lider_id;
                $previousValue = (int) ($item->valor_atual ?? 0);
                $newValue = $previousValue + $step;
            }

            $this->finance->debit(
                (int) $lockedLiga->id,
                (int) $lockedClube->id,
                $newValue,
                'Lance em leilão',
            );

            if ($previousLeaderId && $previousValue > 0) {
                $previousLeader = LigaClube::query()->lockForUpdate()->find($previousLeaderId);
                if ($previousLeader) {
                    $this->finance->credit(
                        (int) $previousLeader->liga_id,
                        (int) $previousLeader->id,
                        $previousValue,
                        'Estorno de lance superado',
                    );
                }
            }

            $expiresAt = $now->copy()->addSeconds(self::BID_DURATION_SECONDS);

            $item->valor_inicial = $baseValue;
            $item->valor_atual = $newValue;
            $item->clube_lider_id = (int) $lockedClube->id;
            $item->expira_em = $expiresAt;
            $item->status = 'aberto';
            $item->motivo_cancelamento = null;
            $item->finalized_at = null;
            $item->save();

            LigaLeilaoLance::create([
                'liga_leilao_item_id' => $item->id,
                'confederacao_id' => $item->confederacao_id,
                'elencopadrao_id' => $item->elencopadrao_id,
                'clube_id' => (int) $lockedClube->id,
                'valor' => $newValue,
                'expira_em' => $expiresAt,
            ]);

            return $this->buildAuctionSnapshotForPlayers(
                $lockedLiga,
                [(int) $item->elencopadrao_id],
                (int) $lockedClube->id,
            )[(int) $item->elencopadrao_id] ?? [];
        }, 3);
    }

    public function finalizeExpiredAuctions(?int $confederacaoId = null): int
    {
        $now = Carbon::now('UTC');

        $query = LigaLeilaoItem::query()
            ->where('status', 'aberto')
            ->whereNotNull('clube_lider_id')
            ->whereNotNull('expira_em')
            ->where('expira_em', '<=', $now);

        if ($confederacaoId) {
            $query->where('confederacao_id', $confederacaoId);
        }

        $itemIds = $query->orderBy('id')->pluck('id');

        $finalized = 0;
        foreach ($itemIds as $itemId) {
            if ($this->finalizeExpiredItemById((int) $itemId, $now)) {
                $finalized++;
            }
        }

        return $finalized;
    }

    public function buildAuctionSnapshotForPlayers(Liga $liga, array $playerIds, ?int $viewerClubId = null): array
    {
        $confederacaoId = (int) ($liga->confederacao_id ?? 0);
        if ($confederacaoId <= 0) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $playerIds), fn ($id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $now = Carbon::now('UTC');

        $items = LigaLeilaoItem::query()
            ->with(['clubeLider:id,nome'])
            ->where('confederacao_id', $confederacaoId)
            ->whereIn('elencopadrao_id', $ids)
            ->get();

        $userBidLookup = [];
        if ($viewerClubId !== null && $viewerClubId > 0 && $items->isNotEmpty()) {
            $bidItemIds = LigaLeilaoLance::query()
                ->whereIn('liga_leilao_item_id', $items->pluck('id')->all())
                ->where('clube_id', $viewerClubId)
                ->distinct()
                ->pluck('liga_leilao_item_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $userBidLookup = array_fill_keys($bidItemIds, true);
        }

        $snapshot = [];

        foreach ($items as $item) {
            $hasBid = $item->status === 'aberto'
                && $item->clube_lider_id
                && $item->expira_em
                && (int) ($item->valor_atual ?? 0) > 0;

            $secondsRemaining = null;
            if ($hasBid) {
                $secondsRemaining = max(0, $now->diffInSeconds($item->expira_em, false));
                if ($secondsRemaining <= 0) {
                    $hasBid = false;
                }
            }

            $leaderClubId = $hasBid ? (int) $item->clube_lider_id : null;
            $currentBid = $hasBid ? (int) ($item->valor_atual ?? 0) : null;

            $snapshot[(int) $item->elencopadrao_id] = [
                'enabled' => true,
                'item_id' => (int) $item->id,
                'status' => (string) $item->status,
                'has_bid' => $hasBid,
                'has_user_bid' => isset($userBidLookup[(int) $item->id]),
                'base_value_eur' => (int) ($item->valor_inicial ?? 0),
                'current_bid_eur' => $currentBid,
                'leader_club_id' => $leaderClubId,
                'leader_club_name' => $hasBid ? ($item->clubeLider?->nome ?? null) : null,
                'expires_at' => $hasBid && $item->expira_em ? $item->expira_em->toIso8601String() : null,
                'seconds_remaining' => $hasBid ? $secondsRemaining : null,
                'is_leader' => $hasBid && $viewerClubId !== null ? $leaderClubId === (int) $viewerClubId : false,
                'next_min_bid_eur' => $hasBid
                    ? ((int) ($item->valor_atual ?? 0) + (int) min(self::BID_INCREMENT_OPTIONS))
                    : (int) ($item->valor_inicial ?? 0),
            ];
        }

        return $snapshot;
    }

    private function resolveInitialBidValue(int $marketValue): int
    {
        $normalizedValue = max(0, $marketValue);
        $initialBid = (int) floor($normalizedValue * 0.8);

        return max(1, $initialBid);
    }

    private function finalizeExpiredItemById(int $itemId, Carbon $now): bool
    {
        return DB::transaction(function () use ($itemId, $now): bool {
            $item = LigaLeilaoItem::query()
                ->whereKey($itemId)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                return false;
            }

            if (
                $item->status !== 'aberto'
                || ! $item->clube_lider_id
                || ! $item->expira_em
                || $item->expira_em->gt($now)
            ) {
                return false;
            }

            $leaderClub = LigaClube::query()->lockForUpdate()->find((int) $item->clube_lider_id);
            if (! $leaderClub) {
                $this->cancelItemAndRefund($item, 'Clube líder indisponível.', $now);
                return true;
            }

            $winnerLiga = Liga::query()->lockForUpdate()->find((int) $leaderClub->liga_id);
            if (! $winnerLiga) {
                $this->cancelItemAndRefund($item, 'Liga do clube líder indisponível.', $now);
                return true;
            }

            $player = Elencopadrao::query()->lockForUpdate()->find((int) $item->elencopadrao_id);
            if (! $player) {
                $this->cancelItemAndRefund($item, 'Jogador indisponível.', $now);
                return true;
            }

            if ($this->isPlayerTakenInScope($winnerLiga, (int) $item->elencopadrao_id)) {
                $this->cancelItemAndRefund($item, 'Jogador não está mais livre.', $now);
                return true;
            }

            try {
                $this->assertRosterLimit($winnerLiga, (int) $leaderClub->id, (int) $leaderClub->liga_id);
            } catch (\DomainException $exception) {
                $this->cancelItemAndRefund($item, 'Leilao cancelado: elenco do clube lider esta cheio.', $now);
                return true;
            }

            LigaClubeElenco::create([
                'confederacao_id' => (int) $item->confederacao_id,
                'liga_id' => (int) $leaderClub->liga_id,
                'liga_clube_id' => (int) $leaderClub->id,
                'elencopadrao_id' => (int) $item->elencopadrao_id,
                'value_eur' => (int) ($player->value_eur ?? 0),
                'wage_eur' => (int) ($player->wage_eur ?? 0),
                'ativo' => true,
            ]);

            LigaTransferencia::create([
                'liga_id' => (int) $leaderClub->liga_id,
                'confederacao_id' => (int) $item->confederacao_id,
                'liga_origem_id' => null,
                'liga_destino_id' => (int) $leaderClub->liga_id,
                'elencopadrao_id' => (int) $item->elencopadrao_id,
                'clube_origem_id' => null,
                'clube_destino_id' => (int) $leaderClub->id,
                'tipo' => 'jogador_livre',
                'valor' => (int) ($item->valor_atual ?? 0),
                'observacao' => 'Jogador livre adquirido via leilão.',
            ]);

            $item->status = 'encerrado';
            $item->motivo_cancelamento = null;
            $item->finalized_at = $now;
            $item->expira_em = null;
            $item->save();

            return true;
        }, 3);
    }

    private function cancelItemAndRefund(LigaLeilaoItem $item, string $reason, Carbon $now): void
    {
        $leaderClubId = (int) ($item->clube_lider_id ?? 0);
        $currentValue = (int) ($item->valor_atual ?? 0);

        if ($leaderClubId > 0 && $currentValue > 0) {
            $leaderClub = LigaClube::query()->lockForUpdate()->find($leaderClubId);
            if ($leaderClub) {
                $this->finance->credit(
                    (int) $leaderClub->liga_id,
                    (int) $leaderClub->id,
                    $currentValue,
                    'Estorno de leilão cancelado',
                );
            }
        }

        $item->status = 'cancelado';
        $item->motivo_cancelamento = $reason;
        $item->finalized_at = $now;
        $item->clube_lider_id = null;
        $item->valor_atual = null;
        $item->expira_em = null;
        $item->save();
    }

    private function assertRosterLimit(Liga $liga, int $clubeId, int $ligaId): void
    {
        $max = (int) ($liga->max_jogadores_por_clube ?? 23);
        $activeCount = LigaClubeElenco::query()
            ->where('liga_id', $ligaId)
            ->where('liga_clube_id', $clubeId)
            ->where('ativo', true)
            ->count();

        if ($activeCount >= $max) {
            throw new \DomainException("Elenco cheio ({$activeCount}/{$max}).");
        }
    }

    private function isPlayerTakenInScope(Liga $liga, int $elencopadraoId): bool
    {
        $query = LigaClubeElenco::query()
            ->where('elencopadrao_id', $elencopadraoId)
            ->where('ativo', true);

        if ($liga->confederacao_id) {
            $query->where('confederacao_id', $liga->confederacao_id);
        } else {
            $query->where('liga_id', $liga->id);
        }

        return $query->exists();
    }
}
