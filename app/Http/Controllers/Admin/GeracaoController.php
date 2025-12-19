<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Geracao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GeracaoController extends Controller
{
    public function index(): View
    {
        $geracoes = Geracao::orderByDesc('created_at')
            ->withCount('ligas')
            ->get();

        return view('admin.geracoes.index', [
            'geracoes' => $geracoes,
        ]);
    }

    public function create(): View
    {
        return view('admin.geracoes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $data['slug'] = $this->generateSlug($data['nome']);

        Geracao::create($data);

        return redirect()->route('admin.geracoes.index')->with('success', 'Geração criada com sucesso.');
    }

    public function edit(Geracao $geracao): View
    {
        $geracao->loadCount('ligas');

        return view('admin.geracoes.edit', [
            'geracao' => $geracao,
        ]);
    }

    public function update(Request $request, Geracao $geracao): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $data['slug'] = $this->generateSlug($data['nome'], $geracao->id);

        $geracao->update($data);

        return redirect()->route('admin.geracoes.index')->with('success', 'Geração atualizada com sucesso.');
    }

    public function destroy(Geracao $geracao): RedirectResponse
    {
        if ($geracao->ligas()->exists()) {
            abort(403);
        }

        $geracao->delete();

        return redirect()->route('admin.geracoes.index')->with('success', 'Geração removida com sucesso.');
    }

    private function generateSlug(string $nome, ?int $ignoreId = null): string
    {
        $slug = Str::slug($nome);
        $original = $slug;
        $counter = 1;

        while (Geracao::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
