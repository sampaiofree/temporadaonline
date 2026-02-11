<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Temporada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemporadaController extends Controller
{
    public function index(): View
    {
        $temporadas = Temporada::with('confederacao')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.temporadas.index', [
            'temporadas' => $temporadas,
            'confederacoes' => Confederacao::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'confederacao_id' => 'required|exists:confederacoes,id',
            'name' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        Temporada::create($data);

        return redirect()->route('admin.temporadas.index')->with('success', 'Temporada criada com sucesso.');
    }

    public function update(Request $request, Temporada $temporada): RedirectResponse
    {
        $data = $request->validate([
            'confederacao_id' => 'required|exists:confederacoes,id',
            'name' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        $temporada->update($data);

        return redirect()->route('admin.temporadas.index')->with('success', 'Temporada atualizada com sucesso.');
    }
}
