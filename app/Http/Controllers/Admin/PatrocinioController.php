<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patrocinio;
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
