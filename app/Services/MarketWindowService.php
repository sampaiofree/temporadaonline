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
        $marketPeriod = LigaPeriodo::activeRangeForLiga($liga);
        $auctionPeriod = LigaLeilao::activeRangeForLiga($liga);

        $mode = self::MODE_CLOSED;
        if ($auctionPeriod) {
            $mode = self::MODE_AUCTION;
        } elseif ($marketPeriod) {
            $mode = self::MODE_OPEN;
        }

        return [
            'mode' => $mode,
            'market_period' => $marketPeriod,
            'match_period' => $marketPeriod, // Backward compatibility for older consumers.
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
