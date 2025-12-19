<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plataforma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlataformaController extends Controller
{
    public function index(): View
    {
        $plataformas = Plataforma::orderByDesc('created_at')
            ->withCount('ligas')
            ->get();

        return view('admin.plataformas.index', [
            'plataformas' => $plataformas,
        ]);
    }

    public function create(): View
    {
        return view('admin.plataformas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $data['slug'] = $this->generateSlug($data['nome']);

        Plataforma::create($data);

        return redirect()->route('admin.plataformas.index')->with('success', 'Plataforma criada com sucesso.');
    }

    public function edit(Plataforma $plataforma): View
    {
        $plataforma->loadCount('ligas');

        return view('admin.plataformas.edit', [
            'plataforma' => $plataforma,
        ]);
    }

    public function update(Request $request, Plataforma $plataforma): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $data['slug'] = $this->generateSlug($data['nome'], $plataforma->id);

        $plataforma->update($data);

        return redirect()->route('admin.plataformas.index')->with('success', 'Plataforma atualizada com sucesso.');
    }

    public function destroy(Plataforma $plataforma): RedirectResponse
    {
        if ($plataforma->ligas()->exists()) {
            abort(403);
        }

        $plataforma->delete();

        return redirect()->route('admin.plataformas.index')->with('success', 'Plataforma removida com sucesso.');
    }

    private function generateSlug(string $nome, ?int $ignoreId = null): string
    {
        $slug = Str::slug($nome);
        $original = $slug;
        $counter = 1;

        while (Plataforma::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
