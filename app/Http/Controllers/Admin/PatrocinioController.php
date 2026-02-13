<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patrocinio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatrocinioController extends Controller
{
    public function index(): View
    {
        $patrocinios = Patrocinio::orderByDesc('created_at')->get();

        return view('admin.patrocinios.index', [
            'patrocinios' => $patrocinios,
        ]);
    }

    public function create(): View
    {
        return view('admin.patrocinios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150|unique:patrocinios,nome',
            'descricao' => 'nullable|string',
            'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'valor' => 'required|integer|min:1',
            'fans' => 'required|integer|min:1',
        ]);

        $path = $request->file('imagem')->store('patrocinios', 'public');

        Patrocinio::create([
            'nome' => trim($validated['nome']),
            'descricao' => $validated['descricao'] !== null ? trim($validated['descricao']) : null,
            'imagem' => $path,
            'valor' => $validated['valor'],
            'fans' => $validated['fans'],
        ]);

        return redirect()
            ->route('admin.patrocinios.index')
            ->with('success', 'Patrocinio criado com sucesso.');
    }

    public function bulkStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'uploads' => 'required|array|min:1',
            'uploads.*.nome' => 'required|string|max:150',
            'uploads.*.descricao' => 'nullable|string',
            'uploads.*.valor' => 'required|integer|min:1',
            'uploads.*.fans' => 'required|integer|min:1',
            'uploads.*.imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $created = 0;

        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.imagem");

            if (! $file) {
                continue;
            }

            $nome = trim($upload['nome']);
            if ($nome === '' || Patrocinio::query()->where('nome', $nome)->exists()) {
                continue;
            }

            $path = $file->store('patrocinios', 'public');

            Patrocinio::create([
                'nome' => $nome,
                'descricao' => filled($upload['descricao'] ?? null) ? trim($upload['descricao']) : null,
                'imagem' => $path,
                'valor' => (int) $upload['valor'],
                'fans' => (int) $upload['fans'],
            ]);

            $created++;
        }

        $message = $created
            ? "{$created} patrocinios importados com sucesso."
            : 'Nenhum patrocinio importado.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.patrocinios.index');
    }

    public function edit(Patrocinio $patrocinio): View
    {
        return view('admin.patrocinios.edit', [
            'patrocinio' => $patrocinio,
        ]);
    }

    public function update(Request $request, Patrocinio $patrocinio): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:150',
                Rule::unique('patrocinios', 'nome')->ignore($patrocinio->id),
            ],
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'valor' => 'required|integer|min:1',
            'fans' => 'required|integer|min:1',
        ]);

        $data = [
            'nome' => trim($validated['nome']),
            'descricao' => $validated['descricao'] !== null ? trim($validated['descricao']) : null,
            'valor' => $validated['valor'],
            'fans' => $validated['fans'],
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('patrocinios', 'public');
            if ($patrocinio->imagem) {
                Storage::disk('public')->delete($patrocinio->imagem);
            }
            $data['imagem'] = $path;
        }

        $patrocinio->update($data);

        return redirect()
            ->route('admin.patrocinios.index')
            ->with('success', 'Patrocinio atualizado com sucesso.');
    }

    public function destroy(Patrocinio $patrocinio): RedirectResponse
    {
        if ($patrocinio->imagem) {
            Storage::disk('public')->delete($patrocinio->imagem);
        }

        $patrocinio->delete();

        return redirect()
            ->route('admin.patrocinios.index')
            ->with('success', 'Patrocinio removido com sucesso.');
    }
}
