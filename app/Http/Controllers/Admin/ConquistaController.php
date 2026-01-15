<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conquista;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConquistaController extends Controller
{
    public function index(): View
    {
        $conquistas = Conquista::orderByDesc('created_at')->get();

        return view('admin.conquistas.index', [
            'conquistas' => $conquistas,
            'tipos' => Conquista::TIPOS,
        ]);
    }

    public function create(): View
    {
        return view('admin.conquistas.create', [
            'tipos' => Conquista::TIPOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'descricao' => 'required|string',
            'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'tipo' => ['required', Rule::in(array_keys(Conquista::TIPOS))],
            'quantidade' => 'required|integer|min:1',
            'fans' => 'required|integer|min:1',
        ]);

        $path = $request->file('imagem')->store('conquistas', 'public');

        Conquista::create([
            'nome' => trim($validated['nome']),
            'descricao' => trim($validated['descricao']),
            'imagem' => $path,
            'tipo' => $validated['tipo'],
            'quantidade' => $validated['quantidade'],
            'fans' => $validated['fans'],
        ]);

        return redirect()
            ->route('admin.conquistas.index')
            ->with('success', 'Conquista criada com sucesso.');
    }

    public function edit(Conquista $conquista): View
    {
        return view('admin.conquistas.edit', [
            'conquista' => $conquista,
            'tipos' => Conquista::TIPOS,
        ]);
    }

    public function update(Request $request, Conquista $conquista): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'descricao' => 'required|string',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'tipo' => ['required', Rule::in(array_keys(Conquista::TIPOS))],
            'quantidade' => 'required|integer|min:1',
            'fans' => 'required|integer|min:1',
        ]);

        $data = [
            'nome' => trim($validated['nome']),
            'descricao' => trim($validated['descricao']),
            'tipo' => $validated['tipo'],
            'quantidade' => $validated['quantidade'],
            'fans' => $validated['fans'],
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('conquistas', 'public');
            if ($conquista->imagem) {
                Storage::disk('public')->delete($conquista->imagem);
            }
            $data['imagem'] = $path;
        }

        $conquista->update($data);

        return redirect()
            ->route('admin.conquistas.index')
            ->with('success', 'Conquista atualizada com sucesso.');
    }

    public function destroy(Conquista $conquista): RedirectResponse
    {
        if ($conquista->imagem) {
            Storage::disk('public')->delete($conquista->imagem);
        }

        $conquista->delete();

        return redirect()
            ->route('admin.conquistas.index')
            ->with('success', 'Conquista removida com sucesso.');
    }
}
