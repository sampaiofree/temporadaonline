<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatrocinioImagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatrocinioImagemController extends Controller
{
    public function index(Request $request): View
    {
        $imagens = PatrocinioImagem::orderBy('nome')
            ->paginate()
            ->withQueryString();

        return view('admin.patrocinios-imagens.index', [
            'imagens' => $imagens,
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

            if ($nome === '' || PatrocinioImagem::where('nome', $nome)->exists()) {
                continue;
            }

            $path = $file->store('patrocinio_imagens', 'public');

            PatrocinioImagem::create([
                'nome' => $nome,
                'url' => $path,
            ]);

            $created++;
        }

        $message = $created
            ? "{$created} imagens de patrocÃ­nios importadas com sucesso."
            : 'Nenhuma imagem importada.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.patrocinios-imagens.index');
    }

    public function edit(PatrocinioImagem $patrocinio_imagem): View
    {
        return view('admin.patrocinios-imagens.edit', [
            'imagem' => $patrocinio_imagem,
        ]);
    }

    public function update(Request $request, PatrocinioImagem $patrocinio_imagem): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150', Rule::unique('patrocinio_imagem')->ignore($patrocinio_imagem->id)],
            'imagem' => 'nullable|image:allow_svg|max:4096',
        ]);

        $data = [
            'nome' => trim($validated['nome']),
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('patrocinio_imagens', 'public');
            if ($patrocinio_imagem->url) {
                Storage::disk('public')->delete($patrocinio_imagem->url);
            }
            $data['url'] = $path;
        }

        $patrocinio_imagem->update($data);

        return redirect()
            ->route('admin.patrocinios-imagens.index')
            ->with('success', 'Imagem atualizada com sucesso.');
    }

    public function destroy(Request $request, PatrocinioImagem $patrocinio_imagem): RedirectResponse
    {
        if ($patrocinio_imagem->url) {
            Storage::disk('public')->delete($patrocinio_imagem->url);
        }

        $patrocinio_imagem->delete();

        return redirect()
            ->route('admin.patrocinios-imagens.index', $request->query())
            ->with('success', 'Imagem removida com sucesso.');
    }
}