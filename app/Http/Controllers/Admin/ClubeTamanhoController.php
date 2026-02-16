<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubeTamanho;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClubeTamanhoController extends Controller
{
    public function index(): View
    {
        return view('admin.clube-tamanho.index', [
            'clubesTamanho' => ClubeTamanho::query()
                ->orderBy('n_fans')
                ->orderBy('nome')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.clube-tamanho.form', [
            'item' => new ClubeTamanho(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('clube_tamanho', 'public');
        }

        ClubeTamanho::create($data);

        return redirect()
            ->route('admin.clube-tamanho.index')
            ->with('success', 'Registro criado com sucesso.');
    }

    public function edit(ClubeTamanho $clube_tamanho): View
    {
        return view('admin.clube-tamanho.form', [
            'item' => $clube_tamanho,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, ClubeTamanho $clube_tamanho): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($request->hasFile('imagem')) {
            $newPath = $request->file('imagem')->store('clube_tamanho', 'public');

            if ($clube_tamanho->imagem) {
                Storage::disk('public')->delete($clube_tamanho->imagem);
            }

            $data['imagem'] = $newPath;
        }

        $clube_tamanho->update($data);

        return redirect()
            ->route('admin.clube-tamanho.index')
            ->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(ClubeTamanho $clube_tamanho): RedirectResponse
    {
        if ($clube_tamanho->imagem) {
            Storage::disk('public')->delete($clube_tamanho->imagem);
        }

        $clube_tamanho->delete();

        return redirect()
            ->route('admin.clube-tamanho.index')
            ->with('success', 'Registro removido com sucesso.');
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'imagem' => ['nullable', 'image:allow_svg', 'max:4096'],
            'n_fans' => ['required', 'integer', 'min:0'],
        ]);

        $validated['nome'] = trim($validated['nome']);

        return $validated;
    }
}
