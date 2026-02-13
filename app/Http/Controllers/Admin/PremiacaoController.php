<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Premiacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PremiacaoController extends Controller
{
    public function index(): View
    {
        $premiacoes = Premiacao::orderBy('posicao')->get();

        return view('admin.premiacoes.index', [
            'premiacoes' => $premiacoes,
        ]);
    }

    public function create(): View
    {
        return view('admin.premiacoes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'posicao' => 'required|integer|min:1|unique:premiacoes,posicao',
            'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'premiacao' => 'required|integer|min:1',
        ]);

        $path = $request->file('imagem')->store('premiacoes', 'public');

        Premiacao::create([
            'posicao' => $validated['posicao'],
            'imagem' => $path,
            'premiacao' => $validated['premiacao'],
        ]);

        return redirect()
            ->route('admin.premiacoes.index')
            ->with('success', 'Premiação criada com sucesso.');
    }

    public function bulkStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'uploads' => 'required|array|min:1',
            'uploads.*.posicao' => 'required|integer|min:1',
            'uploads.*.premiacao' => 'required|integer|min:1',
            'uploads.*.imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $created = 0;

        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.imagem");

            if (! $file) {
                continue;
            }

            $posicao = (int) $upload['posicao'];
            if (Premiacao::query()->where('posicao', $posicao)->exists()) {
                continue;
            }

            $path = $file->store('premiacoes', 'public');

            Premiacao::create([
                'posicao' => $posicao,
                'premiacao' => (int) $upload['premiacao'],
                'imagem' => $path,
            ]);

            $created++;
        }

        $message = $created
            ? "{$created} premiacoes importadas com sucesso."
            : 'Nenhuma premiacao importada.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.premiacoes.index');
    }

    public function edit(Premiacao $premiacao): View
    {
        return view('admin.premiacoes.edit', [
            'premiacao' => $premiacao,
        ]);
    }

    public function update(Request $request, Premiacao $premiacao): RedirectResponse
    {
        $validated = $request->validate([
            'posicao' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('premiacoes', 'posicao')->ignore($premiacao->id),
            ],
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'premiacao' => 'required|integer|min:1',
        ]);

        $data = [
            'posicao' => $validated['posicao'],
            'premiacao' => $validated['premiacao'],
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('premiacoes', 'public');
            if ($premiacao->imagem) {
                Storage::disk('public')->delete($premiacao->imagem);
            }
            $data['imagem'] = $path;
        }

        $premiacao->update($data);

        return redirect()
            ->route('admin.premiacoes.index')
            ->with('success', 'Premiação atualizada com sucesso.');
    }

    public function destroy(Premiacao $premiacao): RedirectResponse
    {
        if ($premiacao->imagem) {
            Storage::disk('public')->delete($premiacao->imagem);
        }

        $premiacao->delete();

        return redirect()
            ->route('admin.premiacoes.index')
            ->with('success', 'Premiação removida com sucesso.');
    }
}
