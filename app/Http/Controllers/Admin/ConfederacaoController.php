<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
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
        return view('admin.confederacoes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:150|unique:confederacoes,nome',
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image:allow_svg|max:2048',
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
        ]);
    }

    public function update(Request $request, Confederacao $confederacao): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:150|unique:confederacoes,nome,'.$confederacao->id,
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image:allow_svg|max:2048',
        ]);

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
