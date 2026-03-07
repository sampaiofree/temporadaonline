<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeAjusteSalarial;
use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
use App\Services\SalaryReserveGuardService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElencoController extends Controller
{
    public function venderMercado(Request $request, LigaClubeElenco $elenco, TransferService $transferService): JsonResponse
    {
        $this->authorizeOwnership($request, $elenco);

        $elenco->loadMissing('liga');
        $liga = $elenco->liga;

        if ($liga && ! LigaPeriodo::activeRangeForLiga($liga)) {
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

    public function updateValor(
        Request $request,
        LigaClubeElenco $elenco,
        SalaryReserveGuardService $salaryReserveGuard,
    ): JsonResponse
    {
        $this->authorizeOwnership($request, $elenco);

        $validated = $request->validate([
            'value_eur' => ['required', 'integer', 'min:0'],
            'wage_eur' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $savedEntry = DB::transaction(function () use ($request, $elenco, $validated, $salaryReserveGuard): LigaClubeElenco {
                $entry = LigaClubeElenco::query()
                    ->lockForUpdate()
                    ->findOrFail($elenco->id);

                $club = LigaClube::query()
                    ->lockForUpdate()
                    ->find($entry->liga_clube_id);

                if (! $club || (int) $club->user_id !== (int) ($request->user()?->id ?? 0)) {
                    abort(403);
                }

                $liga = Liga::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $entry->liga_id);

                $currentWage = (int) ($entry->wage_eur ?? 0);
                $nextWage = array_key_exists('wage_eur', $validated)
                    ? (int) $validated['wage_eur']
                    : $currentWage;

                $reserveDelta = (bool) $entry->ativo
                    ? ($nextWage - $currentWage)
                    : 0;

                $salaryReserveGuard->assertReserveDoesNotExceedBalance(
                    liga: $liga,
                    clubeId: (int) $entry->liga_clube_id,
                    confederacaoId: $entry->confederacao_id ? (int) $entry->confederacao_id : null,
                    reserveDelta: $reserveDelta,
                    balanceDelta: 0,
                );

                $entry->value_eur = (int) $validated['value_eur'];
                if (array_key_exists('wage_eur', $validated)) {
                    $entry->wage_eur = $nextWage;
                }
                $entry->save();

                if ($nextWage !== $currentWage) {
                    $confederacaoId = (int) ($entry->confederacao_id ?? $liga->confederacao_id ?? 0);
                    if ($confederacaoId <= 0) {
                        return $entry;
                    }

                    LigaClubeAjusteSalarial::query()->create([
                        'user_id' => (int) $club->user_id,
                        'confederacao_id' => $confederacaoId,
                        'liga_id' => (int) $entry->liga_id,
                        'liga_clube_id' => (int) $entry->liga_clube_id,
                        'liga_clube_elenco_id' => (int) $entry->id,
                        'wage_anterior' => $currentWage,
                        'wage_novo' => $nextWage,
                    ]);
                }

                return $entry;
            }, 3);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Valor atualizado com sucesso.',
            'value_eur' => $savedEntry->value_eur,
            'wage_eur' => $savedEntry->wage_eur,
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
