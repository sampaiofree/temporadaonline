<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConquistaImagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConquistaImagemController extends Controller
{
    public function index(Request $request): View
    {
        $imagens = ConquistaImagem::orderBy('nome')
            ->paginate()
            ->withQueryString();

        return view('admin.conquistas-imagens.index', [
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

            if ($nome === '' || ConquistaImagem::where('nome', $nome)->exists()) {
                continue;
            }

            $path = $file->store('conquista_imagens', 'public');

            ConquistaImagem::create([
                'nome' => $nome,
                'url' => $path,
            ]);

            $created++;
        }

        $message = $created
            ? "{$created} imagens de conquistas importadas com sucesso."
            : 'Nenhuma imagem importada.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.conquistas-imagens.index');
    }

    public function edit(ConquistaImagem $conquista_imagem): View
    {
        return view('admin.conquistas-imagens.edit', [
            'imagem' => $conquista_imagem,
        ]);
    }

    public function update(Request $request, ConquistaImagem $conquista_imagem): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150', Rule::unique('conquista_imagem')->ignore($conquista_imagem->id)],
            'imagem' => 'nullable|image:allow_svg|max:4096',
        ]);

        $data = [
            'nome' => trim($validated['nome']),
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('conquista_imagens', 'public');
            if ($conquista_imagem->url) {
                Storage::disk('public')->delete($conquista_imagem->url);
            }
            $data['url'] = $path;
        }

        $conquista_imagem->update($data);

        return redirect()
            ->route('admin.conquistas-imagens.index')
            ->with('success', 'Imagem atualizada com sucesso.');
    }

    public function destroy(Request $request, ConquistaImagem $conquista_imagem): RedirectResponse
    {
        if ($conquista_imagem->url) {
            Storage::disk('public')->delete($conquista_imagem->url);
        }

        $conquista_imagem->delete();

        return redirect()
            ->route('admin.conquistas-imagens.index', $request->query())
            ->with('success', 'Imagem removida com sucesso.');
    }
}