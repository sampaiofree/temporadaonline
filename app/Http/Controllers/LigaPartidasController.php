<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Liga;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaPartidasController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);

        return view('liga_partidas', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'jogo' => $liga->jogo?->nome,
            ],
            'clube' => $clube ? [
                'id' => $clube->id,
                'nome' => $clube->nome,
            ] : null,
            'minhas_partidas' => [],
            'todas_partidas' => [],
            'appContext' => $this->makeAppContext($liga, $clube, 'partidas'),
        ]);
    }
}
