<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LigaController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();
        $ligas = Liga::with(['jogo', 'geracao', 'plataforma', 'users'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Liga $liga) => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'descricao' => $liga->descricao,
                'regras' => $liga->regras,
                'imagem' => $liga->imagem,
                'tipo' => $liga->tipo,
                'status' => $liga->status,
                'max_times' => $liga->max_times,
                'jogo' => $liga->jogo?->nome,
                'geracao' => $liga->geracao?->nome,
                'plataforma' => $liga->plataforma?->nome,
                'registered' => $userId ? $liga->users->contains($userId) : false,
                'created_at' => $liga->created_at?->toIso8601String(),
            ])
            ->all();

        $myLigas = array_values(array_filter($ligas, fn (array $liga) => $liga['registered']));

        return view('ligas', [
            'ligas' => $ligas,
            'myLigas' => $myLigas,
            'appContext' => [
                'mode' => 'global',
                'liga' => null,
                'clube' => null,
                'nav' => 'ligas',
            ],
        ]);
    }

    public function join(Request $request, Liga $liga): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $user->ligas()->syncWithoutDetaching([$liga->id]);

        return response()->json([
            'redirect' => route('minha_liga', ['liga_id' => $liga->id]),
        ]);
    }
}
