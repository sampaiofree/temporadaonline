<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conquista;
use Illuminate\Http\JsonResponse;
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

    public function bulkStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'uploads' => 'required|array|min:1',
            'uploads.*.nome' => 'required|string|max:150',
            'uploads.*.descricao' => 'required|string',
            'uploads.*.tipo' => ['required', Rule::in(array_keys(Conquista::TIPOS))],
            'uploads.*.quantidade' => 'required|integer|min:1',
            'uploads.*.fans' => 'required|integer|min:1',
            'uploads.*.imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $created = 0;

        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.imagem");

            if (! $file) {
                continue;
            }

            $path = $file->store('conquistas', 'public');

            Conquista::create([
                'nome' => trim($upload['nome']),
                'descricao' => trim($upload['descricao']),
                'imagem' => $path,
                'tipo' => $upload['tipo'],
                'quantidade' => (int) $upload['quantidade'],
                'fans' => (int) $upload['fans'],
            ]);

            $created++;
        }

        $message = $created
            ? "{$created} conquistas importadas com sucesso."
            : 'Nenhuma conquista importada.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.conquistas.index');
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
