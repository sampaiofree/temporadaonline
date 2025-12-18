<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\LigaClube;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaClassificacaoController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);

        $clube = $this->resolveUserClub($request);

        $clubs = LigaClube::query()
            ->where('liga_id', $liga->id)
            ->with('user')
            ->orderBy('nome')
            ->get();

        $classification = $clubs->values()->map(function (LigaClube $clube, $index) {
            return [
                'posicao' => $index + 1,
                'clube_id' => $clube->id,
                'clube_nome' => $clube->nome,
                'dono' => $clube->user?->name,
                'pontos' => 0,
                'vitorias' => 0,
            ];
        });

        return view('liga_classificacao', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            ],
            'classification' => $classification,
            'appContext' => $this->makeAppContext($liga, $clube),
        ]);
    }
}
