<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Idioma;
use App\Models\Regiao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IdiomaRegiaoController extends Controller
{
    public function index(): View
    {
        return view('admin.idioma-regiao.index', [
            'idiomas' => Idioma::query()
                ->withCount('profiles')
                ->orderBy('nome')
                ->get(),
            'regioes' => Regiao::query()
                ->withCount('profiles')
                ->orderBy('nome')
                ->get(),
        ]);
    }

    public function storeIdioma(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        Idioma::create([
            'nome' => $data['nome'],
            'slug' => $this->generateSlug(Idioma::class, $data['nome']),
        ]);

        return redirect()
            ->route('admin.idioma-regiao.index')
            ->with('success', 'Idioma criado com sucesso.');
    }

    public function updateIdioma(Request $request, Idioma $idioma): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        $idioma->update([
            'nome' => $data['nome'],
            'slug' => $this->generateSlug(Idioma::class, $data['nome'], $idioma->id),
        ]);

        return redirect()
            ->route('admin.idioma-regiao.index')
            ->with('success', 'Idioma atualizado com sucesso.');
    }

    public function destroyIdioma(Idioma $idioma): RedirectResponse
    {
        $idioma->delete();

        return redirect()
            ->route('admin.idioma-regiao.index')
            ->with('success', 'Idioma removido com sucesso.');
    }

    public function storeRegiao(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        Regiao::create([
            'nome' => $data['nome'],
            'slug' => $this->generateSlug(Regiao::class, $data['nome']),
        ]);

        return redirect()
            ->route('admin.idioma-regiao.index')
            ->with('success', 'Região criada com sucesso.');
    }

    public function updateRegiao(Request $request, Regiao $regiao): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        $regiao->update([
            'nome' => $data['nome'],
            'slug' => $this->generateSlug(Regiao::class, $data['nome'], $regiao->id),
        ]);

        return redirect()
            ->route('admin.idioma-regiao.index')
            ->with('success', 'Região atualizada com sucesso.');
    }

    public function destroyRegiao(Regiao $regiao): RedirectResponse
    {
        $regiao->delete();

        return redirect()
            ->route('admin.idioma-regiao.index')
            ->with('success', 'Região removida com sucesso.');
    }

    private function generateSlug(string $modelClass, string $nome, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($nome);
        $seed = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));
        $slug = $seed;
        $counter = 1;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$seed}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
