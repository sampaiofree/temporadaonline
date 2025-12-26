<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaisController extends Controller
{
    public function index(): View
    {
        $paises = Pais::orderBy('nome')->get();

        return view('admin.paises.index', [
            'paises' => $paises,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'uploads' => 'required|array|min:1',
            'uploads.*.nome' => 'required|string|max:150',
            'uploads.*.imagem' => 'required|image:allow_svg|max:4096',
        ]);

        $created = 0;
        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.imagem");

            if (! $file) {
                continue;
            }

            $nome = trim($upload['nome']);
            $slug = $this->generateSlug($nome);
            $path = $file->store('paises', 'public');

            Pais::create([
                'nome' => $nome,
                'slug' => $slug,
                'imagem' => $path,
                'ativo' => true,
            ]);

            $created++;
        }

        $request->session()->flash('success', "{$created} país(es) importado(s) com sucesso.");

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.paises.index');
    }

    public function edit(Pais $pais): View
    {
        return view('admin.paises.edit', [
            'pais' => $pais,
        ]);
    }

    public function update(Request $request, Pais $pais): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'imagem' => 'nullable|image:allow_svg|max:4096',
            'ativo' => 'required|boolean',
        ]);

        $nome = trim($validated['nome']);

        $data = [
            'nome' => $nome,
            'slug' => $this->generateSlug($nome, $pais->id),
            'ativo' => (bool) $validated['ativo'],
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('paises', 'public');
            if ($pais->imagem) {
                Storage::disk('public')->delete($pais->imagem);
            }
            $data['imagem'] = $path;
        }

        $pais->update($data);

        return redirect()->route('admin.paises.index')->with('success', 'País atualizado com sucesso.');
    }

    public function destroy(Pais $pais): RedirectResponse
    {
        $pais->delete();

        return redirect()->route('admin.paises.index')->with('success', 'País removido com sucesso.');
    }

    private function generateSlug(string $nome, ?int $ignoreId = null): string
    {
        $slug = Str::slug($nome) ?: 'pais';
        $original = $slug;
        $counter = 1;

        while (Pais::withTrashed()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
