<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaLeilao;
use App\Models\LigaPeriodo;

class MarketWindowService
{
    public const MODE_OPEN = 'open';
    public const MODE_CLOSED = 'closed';
    public const MODE_AUCTION = 'auction';

    public function resolveForLiga(Liga $liga): array
    {
        $matchPeriod = LigaPeriodo::activeRangeForLiga($liga);
        $auctionPeriod = LigaLeilao::activeRangeForLiga($liga);

        $mode = self::MODE_OPEN;
        if ($auctionPeriod) {
            $mode = self::MODE_AUCTION;
        } elseif ($matchPeriod) {
            $mode = self::MODE_CLOSED;
        }

        return [
            'mode' => $mode,
            'match_period' => $matchPeriod,
            'auction_period' => $auctionPeriod,
            'is_open' => $mode === self::MODE_OPEN,
            'is_closed' => $mode === self::MODE_CLOSED,
            'is_auction' => $mode === self::MODE_AUCTION,
        ];
    }

    public function isAuctionActive(Liga $liga): bool
    {
        return (bool) ($this->resolveForLiga($liga)['is_auction'] ?? false);
    }
}

