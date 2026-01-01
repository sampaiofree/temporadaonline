<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\PlayerFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerFavoriteController extends Controller
{
    public function index(Request $request, Liga $liga): JsonResponse
    {
        $userId = $request->user()->id;
        $confederacaoId = $liga->confederacao_id;

        $favorites = PlayerFavorite::query()
            ->where('user_id', $userId)
            ->when(
                $confederacaoId,
                fn ($query) => $query->where('confederacao_id', $confederacaoId),
                fn ($query) => $query->where('liga_id', $liga->id),
            )
            ->orderBy('id')
            ->pluck('elencopadrao_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'favoritos' => $favorites,
        ]);
    }

    public function toggle(Request $request, Liga $liga): JsonResponse
    {
        $user = $request->user();
        $confederacaoId = $liga->confederacao_id;

        $data = $request->validate([
            'elencopadrao_id' => ['required', 'integer', 'exists:elencopadrao,id'],
        ]);

        $favoriteQuery = PlayerFavorite::query()
            ->where('user_id', $user->id)
            ->when(
                $confederacaoId,
                fn ($query) => $query->where('confederacao_id', $confederacaoId),
                fn ($query) => $query->where('liga_id', $liga->id),
            );

        $favorite = $favoriteQuery
            ->where('elencopadrao_id', $data['elencopadrao_id'])
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json([
                'status' => 'removed',
            ]);
        }

        $player = Elencopadrao::query()
            ->where('id', $data['elencopadrao_id'])
            ->first();

        if (! $player || (int) $player->jogo_id !== (int) $liga->jogo_id) {
            return response()->json([
                'message' => 'Jogador nao pertence a esta liga.',
            ], 422);
        }

        PlayerFavorite::create([
            'user_id' => $user->id,
            'liga_id' => $liga->id,
            'confederacao_id' => $confederacaoId,
            'elencopadrao_id' => $data['elencopadrao_id'],
        ]);

        return response()->json([
            'status' => 'added',
        ]);
    }
}
