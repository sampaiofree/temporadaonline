<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BuyPlayerRequest;
use App\Http\Requests\Api\PayReleaseClauseRequest;
use App\Http\Requests\Api\SellPlayerRequest;
use App\Http\Requests\Api\SwapPlayersRequest;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

class LeagueTransferController extends Controller
{
    public function __construct(private readonly TransferService $transferService)
    {
    }

    public function buy(BuyPlayerRequest $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        if ($blocked = $this->ensureMarketOpen($liga)) {
            return $blocked;
        }

        try {
            $entry = $this->transferService->buyPlayer(
                ligaId: (int) $liga->id,
                compradorClubeId: (int) $clube->id,
                elencopadraoId: (int) $request->validated('elencopadrao_id'),
            );

            return response()->json([
                'message' => 'Jogador adicionado ao seu elenco.',
                'entry' => $entry,
            ], 201);
        } catch (\DomainException $exception) {
            $message = $exception->getMessage();
            $status = str_contains($message, 'já faz parte') ? 409 : 422;

            return response()->json(['message' => $message], $status);
        }
    }

    public function sell(SellPlayerRequest $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        if ($blocked = $this->ensureMarketOpen($liga)) {
            return $blocked;
        }

        try {
            $playerId = (int) $request->validated('elencopadrao_id');
            $entry = LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('elencopadrao_id', $playerId)
                ->first();

            if (! $entry) {
                return response()->json([
                    'message' => 'Este jogador está livre. Use a rota de compra de jogador livre.',
                ], 422);
            }

            $entry = $this->transferService->sellPlayer(
                ligaId: (int) $liga->id,
                vendedorClubeId: (int) $entry->liga_clube_id,
                compradorClubeId: (int) $clube->id,
                elencopadraoId: $playerId,
                price: (int) $request->validated('price'),
            );

            return response()->json([
                'message' => 'Transferência concluída com sucesso.',
                'entry' => $entry,
            ]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function payReleaseClause(PayReleaseClauseRequest $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        if ($blocked = $this->ensureMarketOpen($liga)) {
            return $blocked;
        }

        try {
            $entry = $this->transferService->payReleaseClause(
                ligaId: (int) $liga->id,
                compradorClubeId: (int) $clube->id,
                elencopadraoId: (int) $request->validated('elencopadrao_id'),
            );

            return response()->json([
                'message' => 'Multa paga e jogador transferido com sucesso.',
                'entry' => $entry,
            ]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function swap(SwapPlayersRequest $request, Liga $liga, LigaClube $clube): JsonResponse
    {
        if ($blocked = $this->ensureMarketOpen($liga)) {
            return $blocked;
        }

        try {
            $entries = $this->transferService->swapPlayers(
                ligaId: (int) $liga->id,
                clubeAId: (int) $clube->id,
                jogadorAId: (int) $request->validated('jogador_a_id'),
                clubeBId: (int) $request->validated('clube_b_id'),
                jogadorBId: (int) $request->validated('jogador_b_id'),
                ajusteValor: (int) $request->validated('ajuste_valor', 0),
            );

            return response()->json([
                'message' => 'Troca realizada com sucesso.',
                'entries' => $entries,
            ]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    private function ensureMarketOpen(Liga $liga): ?JsonResponse
    {
        $periodo = LigaPeriodo::activeRangeForLiga($liga);
        if (! $periodo) {
            return null;
        }

        $inicioLabel = $periodo['inicio_label'] ?? null;
        $fimLabel = $periodo['fim_label'] ?? null;
        $range = $inicioLabel && $fimLabel ? " ({$inicioLabel} até {$fimLabel})" : '';

        return response()->json([
            'message' => "Mercado fechado durante o período de partidas{$range}.",
        ], 423);
    }
}
