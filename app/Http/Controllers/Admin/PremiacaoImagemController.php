<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PremiacaoImagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PremiacaoImagemController extends Controller
{
    public function index(Request $request): View
    {
        $imagens = PremiacaoImagem::orderBy('nome')
            ->paginate()
            ->withQueryString();

        return view('admin.premiacoes-imagens.index', [
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

            if ($nome === '' || PremiacaoImagem::where('nome', $nome)->exists()) {
                continue;
            }

            $path = $file->store('premiacao_imagens', 'public');

            PremiacaoImagem::create([
                'nome' => $nome,
                'url' => $path,
            ]);

            $created++;
        }

        $message = $created
            ? "{$created} imagens de premiaÃ§Ãµes importadas com sucesso."
            : 'Nenhuma imagem importada.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.premiacoes-imagens.index');
    }

    public function edit(PremiacaoImagem $premiacao_imagem): View
    {
        return view('admin.premiacoes-imagens.edit', [
            'imagem' => $premiacao_imagem,
        ]);
    }

    public function update(Request $request, PremiacaoImagem $premiacao_imagem): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150', Rule::unique('premiacao_imagem')->ignore($premiacao_imagem->id)],
            'imagem' => 'nullable|image:allow_svg|max:4096',
        ]);

        $data = [
            'nome' => trim($validated['nome']),
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('premiacao_imagens', 'public');
            if ($premiacao_imagem->url) {
                Storage::disk('public')->delete($premiacao_imagem->url);
            }
            $data['url'] = $path;
        }

        $premiacao_imagem->update($data);

        return redirect()
            ->route('admin.premiacoes-imagens.index')
            ->with('success', 'Imagem atualizada com sucesso.');
    }

    public function destroy(Request $request, PremiacaoImagem $premiacao_imagem): RedirectResponse
    {
        if ($premiacao_imagem->url) {
            Storage::disk('public')->delete($premiacao_imagem->url);
        }

        $premiacao_imagem->delete();

        return redirect()
            ->route('admin.premiacoes-imagens.index', $request->query())
            ->with('success', 'Imagem removida com sucesso.');
    }
}