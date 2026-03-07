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
        $reclamacoes = ReclamacaoPartida::query()
            ->with(['partida.liga', 'partida.mandante', 'partida.visitante', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.partida_reclamacoes.index', [
            'reclamacoes' => $reclamacoes,
        ]);
    }
}
