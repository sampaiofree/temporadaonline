<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceAuctionBidRequest;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Services\AuctionService;
use App\Services\MarketWindowService;
use Illuminate\Http\JsonResponse;

class LeagueAuctionController extends Controller
{
    public function __construct(
        private readonly AuctionService $auctionService,
        private readonly MarketWindowService $marketWindowService,
    ) {
    }

    public function bid(PlaceAuctionBidRequest $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        $window = $this->marketWindowService->resolveForLiga($liga);
        if (! ($window['is_auction'] ?? false)) {
            return response()->json([
                'message' => 'Mercado fora do periodo de leilao. Lances indisponiveis.',
            ], 423);
        }

        try {
            $snapshot = $this->auctionService->placeBid(
                liga: $liga,
                clube: $clube,
                elencopadraoId: (int) $request->validated('elencopadrao_id'),
                increment: $request->filled('increment') ? (int) $request->validated('increment') : null,
            );

            return response()->json([
                'message' => 'Lance registrado com sucesso.',
                'auction' => $snapshot,
            ], 201);
        } catch (\DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}

