<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Liga;
use App\Models\LigaLeilao;
use App\Models\LigaPeriodo;
use App\Models\LigaRouboMulta;

class MarketWindowService
{
    public const MODE_OPEN = 'open';
    public const MODE_CLOSED = 'closed';
    public const MODE_AUCTION = 'auction';

    public function resolveForLiga(Liga $liga): array
    {
        $marketPeriod = LigaPeriodo::activeRangeForLiga($liga);
        $auctionPeriod = LigaLeilao::activeRangeForLiga($liga);
        $multaPeriod = LigaRouboMulta::activeRangeForLiga($liga);
        $nextMarketPeriod = $this->resolveNextMarketPeriod($liga, $marketPeriod);
        $nextAuctionPeriod = $this->resolveNextAuctionPeriod($liga, $auctionPeriod);
        $nextMultaPeriod = $this->resolveNextMultaPeriod($liga, $multaPeriod);

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
            'multa_period' => $multaPeriod,
            'next_market_period' => $nextMarketPeriod,
            'next_auction_period' => $nextAuctionPeriod,
            'next_multa_period' => $nextMultaPeriod,
            'is_multa_open' => (bool) $multaPeriod,
            'is_open' => $mode === self::MODE_OPEN,
            'is_closed' => $mode === self::MODE_CLOSED,
            'is_auction' => $mode === self::MODE_AUCTION,
        ];
    }

    public function isAuctionActive(Liga $liga): bool
    {
        return (bool) ($this->resolveForLiga($liga)['is_auction'] ?? false);
    }

    private function resolveNextMarketPeriod(Liga $liga, ?array $currentPeriod): ?array
    {
        if (! $liga->confederacao_id) {
            return null;
        }

        $tz = $liga->resolveTimezone();
        $threshold = $currentPeriod['fim'] ?? Carbon::now($tz)->format('Y-m-d H:i:s');

        $nextRange = LigaPeriodo::query()
            ->where('confederacao_id', $liga->confederacao_id)
            ->where('inicio', '>', $threshold)
            ->orderBy('inicio')
            ->first(['inicio', 'fim']);

        if (! $nextRange) {
            return null;
        }

        $startDate = Carbon::parse((string) $nextRange->inicio, $tz);
        $endDate = Carbon::parse((string) $nextRange->fim, $tz);

        return [
            'inicio' => $startDate->format('Y-m-d\TH:i:s'),
            'fim' => $endDate->format('Y-m-d\TH:i:s'),
            'inicio_label' => $startDate->format('d/m/Y H:i'),
            'fim_label' => $endDate->format('d/m/Y H:i'),
            'timezone' => $tz,
        ];
    }

    private function resolveNextAuctionPeriod(Liga $liga, ?array $currentPeriod): ?array
    {
        if (! $liga->confederacao_id) {
            return null;
        }

        $tz = $liga->resolveTimezone();
        $threshold = $currentPeriod['fim'] ?? Carbon::now($tz)->toDateString();

        $nextRange = LigaLeilao::query()
            ->where('confederacao_id', $liga->confederacao_id)
            ->whereDate('inicio', '>', $threshold)
            ->orderBy('inicio')
            ->first(['inicio', 'fim']);

        if (! $nextRange) {
            return null;
        }

        $startDate = $nextRange->inicio instanceof Carbon ? $nextRange->inicio : Carbon::parse((string) $nextRange->inicio, $tz);
        $endDate = $nextRange->fim instanceof Carbon ? $nextRange->fim : Carbon::parse((string) $nextRange->fim, $tz);

        return [
            'inicio' => $startDate->toDateString(),
            'fim' => $endDate->toDateString(),
            'inicio_label' => $startDate->format('d/m/Y'),
            'fim_label' => $endDate->format('d/m/Y'),
            'timezone' => $tz,
        ];
    }

    private function resolveNextMultaPeriod(Liga $liga, ?array $currentPeriod): ?array
    {
        if (! $liga->confederacao_id) {
            return null;
        }

        $tz = $liga->resolveTimezone();
        $threshold = $currentPeriod['fim'] ?? Carbon::now($tz)->format('Y-m-d H:i:s');

        $nextRange = LigaRouboMulta::query()
            ->where('confederacao_id', $liga->confederacao_id)
            ->where('inicio', '>', $threshold)
            ->orderBy('inicio')
            ->first(['inicio', 'fim']);

        if (! $nextRange) {
            return null;
        }

        $startDate = $nextRange->inicio instanceof Carbon ? $nextRange->inicio : Carbon::parse((string) $nextRange->inicio, $tz);
        $endDate = $nextRange->fim instanceof Carbon ? $nextRange->fim : Carbon::parse((string) $nextRange->fim, $tz);

        return [
            'inicio' => $startDate->format('Y-m-d H:i:s'),
            'fim' => $endDate->format('Y-m-d H:i:s'),
            'inicio_label' => $startDate->format('d/m/Y H:i'),
            'fim_label' => $endDate->format('d/m/Y H:i'),
            'timezone' => $tz,
        ];
    }
}
