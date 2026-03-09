<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReclamacaoPartida;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartidaReclamacaoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'partida_id' => trim((string) $request->query('partida_id', '')),
        ];

        $reclamacoesQuery = ReclamacaoPartida::query()
            ->with(['partida.liga', 'partida.mandante', 'partida.visitante', 'user'])
            ->latest();

        if ($filters['partida_id'] !== '') {
            if (ctype_digit($filters['partida_id'])) {
                $reclamacoesQuery->where('partida_id', (int) $filters['partida_id']);
            } else {
                $reclamacoesQuery->whereRaw('1 = 0');
            }
        }

        $reclamacoes = $reclamacoesQuery
            ->paginate(20)
            ->withQueryString();

        return view('admin.partida_reclamacoes.index', [
            'reclamacoes' => $reclamacoes,
            'filters' => $filters,
        ]);
    }
}
