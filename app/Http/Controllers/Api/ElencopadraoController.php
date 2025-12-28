<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use Illuminate\Http\JsonResponse;

class ElencopadraoController extends Controller
{
    public function show(Elencopadrao $player): JsonResponse
    {
        $player->loadMissing('jogo:id,nome');

        $data = $player->toArray();
        if (array_key_exists('jogo', $data)) {
            unset($data['jogo']);
        }

        $data['jogo_nome'] = $player->jogo?->nome;

        return response()->json([
            'player' => $data,
        ]);
    }
}
