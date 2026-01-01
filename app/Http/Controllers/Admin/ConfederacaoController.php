<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Plataforma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ConfederacaoController extends Controller
{
    public function index(): View
    {
        $confederacoes = Confederacao::orderByDesc('created_at')
            ->withCount('ligas')
            ->get();

        return view('admin.confederacoes.index', [
            'confederacoes' => $confederacoes,
        ]);
    }

    public function create(): View
    {
        return view('admin.confederacoes.create', [
            'jogos' => Jogo::orderBy('nome')->get(),
            'geracoes' => Geracao::orderBy('nome')->get(),
            'plataformas' => Plataforma::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:150|unique:confederacoes,nome',
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'plataforma_id' => 'required|exists:plataformas,id',
        ]);

        $data['nome'] = trim($data['nome']);
        if (array_key_exists('descricao', $data)) {
            $data['descricao'] = $data['descricao'] !== null ? trim($data['descricao']) : null;
        }

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('confederacoes', 'public');
        }

        Confederacao::create($data);

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao criada com sucesso.');
    }

    public function edit(Confederacao $confederacao): View
    {
        $confederacao->loadCount('ligas');

        return view('admin.confederacoes.edit', [
            'confederacao' => $confederacao,
            'jogos' => Jogo::orderBy('nome')->get(),
            'geracoes' => Geracao::orderBy('nome')->get(),
            'plataformas' => Plataforma::orderBy('nome')->get(),
            'lockSelections' => $confederacao->ligas_count > 0,
        ]);
    }

    public function update(Request $request, Confederacao $confederacao): RedirectResponse
    {
        $hasLigas = $confederacao->ligas()->exists();

        $rules = [
            'nome' => 'required|string|max:150|unique:confederacoes,nome,'.$confederacao->id,
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'plataforma_id' => 'required|exists:plataformas,id',
        ];

        if ($hasLigas) {
            unset($rules['jogo_id'], $rules['geracao_id'], $rules['plataforma_id']);
        }

        $data = $request->validate($rules);

        $data['nome'] = trim($data['nome']);
        if (array_key_exists('descricao', $data)) {
            $data['descricao'] = $data['descricao'] !== null ? trim($data['descricao']) : null;
        }

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('confederacoes', 'public');
            if ($confederacao->imagem) {
                Storage::disk('public')->delete($confederacao->imagem);
            }
            $data['imagem'] = $path;
        }

        if ($hasLigas) {
            unset($data['jogo_id'], $data['geracao_id'], $data['plataforma_id']);
        }

        $confederacao->update($data);

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao atualizada com sucesso.');
    }

    public function destroy(Confederacao $confederacao): RedirectResponse
    {
        if ($confederacao->ligas()->exists()) {
            abort(403);
        }

        if ($confederacao->imagem) {
            Storage::disk('public')->delete($confederacao->imagem);
        }

        $confederacao->delete();

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao removida com sucesso.');
    }
}
