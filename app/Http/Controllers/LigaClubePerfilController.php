<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Liga;
use App\Models\LigaClube;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaClubePerfilController extends Controller
{
    use ResolvesLiga;

    public function show(Request $request, LigaClube $clube): View
    {
        $liga = $this->resolveUserLiga($request);

        if ((int) $clube->liga_id !== (int) $liga->id) {
            abort(404);
        }

        $clube->load([
            'user',
            'clubeElencos.elencopadrao',
        ]);

        $players = $clube->clubeElencos->map(function ($entry) {
            $player = $entry->elencopadrao;

            return [
                'id' => $player?->id,
                'short_name' => $player?->short_name,
                'player_positions' => $player?->player_positions,
                'overall' => $player?->overall,
            ];
        });

        return view('liga_clube_perfil', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            ],
            'clube' => [
                'id' => $clube->id,
                'nome' => $clube->nome,
                'dono' => $clube->user?->name,
                'players' => $players,
            ],
            'appContext' => $this->makeAppContext($liga, $clube),
        ]);
    }
}
