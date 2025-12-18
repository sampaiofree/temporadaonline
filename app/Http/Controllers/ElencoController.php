<?php

namespace App\Http\Controllers;

use App\Models\LigaClubeElenco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElencoController extends Controller
{
    public function venderMercado(Request $request, LigaClubeElenco $elenco): JsonResponse
    {
        $this->authorizeOwnership($request, $elenco);

        return response()->json([
            'message' => 'Jogador devolvido ao mercado com sucesso.',
        ]);
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
