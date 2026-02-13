<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JogoController extends Controller
{
    public function index(): View
    {
        $jogos = Jogo::orderByDesc('created_at')
            ->withCount('ligas')
            ->get();

        return view('admin.jogos.index', [
            'jogos' => $jogos,
        ]);
    }

    public function create(): View
    {
        return view('admin.jogos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'imagem' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
        ]);

        $data['slug'] = $this->generateSlug($data['nome']);

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('jogos', 'public');
        }

        Jogo::create($data);

        return redirect()->route('admin.jogos.index')->with('success', 'Jogo criado com sucesso.');
    }

    public function edit(Jogo $jogo): View
    {
        $jogo->loadCount('ligas');

        return view('admin.jogos.edit', [
            'jogo' => $jogo,
        ]);
    }

    public function update(Request $request, Jogo $jogo): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'imagem' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
        ]);

        $data['slug'] = $this->generateSlug($data['nome'], $jogo->id);

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('jogos', 'public');
            if ($jogo->imagem) {
                Storage::disk('public')->delete($jogo->imagem);
            }
            $data['imagem'] = $path;
        }

        $jogo->update($data);

        return redirect()->route('admin.jogos.index')->with('success', 'Jogo atualizado com sucesso.');
    }

    public function destroy(Jogo $jogo): RedirectResponse
    {
        if ($jogo->ligas()->exists()) {
            abort(403);
        }

        if ($jogo->imagem) {
            Storage::disk('public')->delete($jogo->imagem);
        }

        $jogo->delete();

        return redirect()->route('admin.jogos.index')->with('success', 'Jogo removido com sucesso.');
    }

    private function generateSlug(string $nome, ?int $ignoreId = null): string
    {
        $slug = Str::slug($nome);
        $original = $slug;
        $counter = 1;

        while (Jogo::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
