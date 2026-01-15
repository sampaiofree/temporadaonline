<?php

namespace App\Http\Controllers;

use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElencoController extends Controller
{
    public function venderMercado(Request $request, LigaClubeElenco $elenco, TransferService $transferService): JsonResponse
    {
        $this->authorizeOwnership($request, $elenco);

        $elenco->loadMissing('liga');
        $liga = $elenco->liga;

        if ($liga && LigaPeriodo::activeRangeForLiga($liga)) {
            $activeCount = LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $elenco->liga_clube_id)
                ->where('ativo', true)
                ->count();

            if ($activeCount <= 18) {
                return response()->json([
                    'message' => 'Mercado fechado. Seu elenco já está com 18 jogadores ativos. Vendas bloqueadas.',
                ], 422);
            }
        }

        try {
            $result = $transferService->releaseToMarket($elenco);

            $message = $result['credit'] > 0
                ? "Jogador devolvido ao mercado. Crédito de {$result['credit']} aplicado."
                : 'Jogador devolvido ao mercado.';

            return response()->json([
                'message' => $message,
                'credit' => $result['credit'],
                'base_value' => $result['base_value'],
                'tax_percent' => $result['tax_percent'],
                'tax_value' => $result['tax_value'],
            ]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function listarMercado(Request $request, LigaClubeElenco $elenco): JsonResponse
    {
        $this->authorizeOwnership($request, $elenco);

        $validated = $request->validate([
            'preco' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json([
            'message' => 'Jogador listado no mercado com sucesso.',
            'preco' => (float) $validated['preco'],
        ]);
    }

    public function updateValor(Request $request, LigaClubeElenco $elenco): JsonResponse
    {
        $this->authorizeOwnership($request, $elenco);

        $validated = $request->validate([
            'value_eur' => ['required', 'integer', 'min:0'],
            'wage_eur' => ['nullable', 'integer', 'min:0'],
        ]);

        $elenco->value_eur = (int) $validated['value_eur'];
        if (array_key_exists('wage_eur', $validated)) {
            $elenco->wage_eur = (int) $validated['wage_eur'];
        }
        $elenco->save();

        return response()->json([
            'message' => 'Valor atualizado com sucesso.',
            'value_eur' => $elenco->value_eur,
            'wage_eur' => $elenco->wage_eur,
        ]);
    }

    private function authorizeOwnership(Request $request, LigaClubeElenco $elenco): void
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! $elenco->relationLoaded('ligaClube')) {
            $elenco->load('ligaClube');
        }

        if (! $elenco->ligaClube || (int) $elenco->ligaClube->user_id !== (int) $user->id) {
            abort(403);
        }
    }
}
