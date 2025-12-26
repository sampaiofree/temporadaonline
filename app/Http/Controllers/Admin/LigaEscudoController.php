<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LigaEscudo;
use App\Models\Pais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LigaEscudoController extends Controller
{
    public function index(): View
    {
        $paises = Pais::orderBy('nome')->get();
        $ligas = LigaEscudo::with('pais')->orderBy('liga_nome')->get();

        return view('admin.ligas-escudos.index', [
            'ligas' => $ligas,
            'paises' => $paises,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'default_pais_id' => 'nullable|exists:paises,id',
            'uploads' => 'required|array|min:1',
            'uploads.*.liga_nome' => 'required|string|max:150',
            'uploads.*.pais_id' => 'required|exists:paises,id',
            'uploads.*.liga_imagem' => 'required|image:allow_svg|max:4096',
        ]);

        $created = 0;
        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.liga_imagem");

            if (! $file) {
                continue;
            }

            $path = $file->store('ligas-escudos', 'public');

            LigaEscudo::create([
                'liga_nome' => trim($upload['liga_nome']),
                'pais_id' => $upload['pais_id'],
                'liga_imagem' => $path,
            ]);

            $created++;
        }

        $request->session()->flash('success', "{$created} escudo(s) cadastrado(s) com sucesso.");

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.ligas-escudos.index');
    }

    public function edit(LigaEscudo $ligaEscudo): View
    {
        $paises = Pais::orderBy('nome')->get();

        return view('admin.ligas-escudos.edit', [
            'ligaEscudo' => $ligaEscudo,
            'paises' => $paises,
        ]);
    }

    public function update(Request $request, LigaEscudo $ligaEscudo): RedirectResponse
    {
        $validated = $request->validate([
            'liga_nome' => 'required|string|max:150',
            'pais_id' => 'required|exists:paises,id',
            'imagem' => 'nullable|image:allow_svg|max:4096',
        ]);

        $data = [
            'liga_nome' => trim($validated['liga_nome']),
            'pais_id' => $validated['pais_id'],
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('ligas-escudos', 'public');
            if ($ligaEscudo->liga_imagem) {
                Storage::disk('public')->delete($ligaEscudo->liga_imagem);
            }
            $data['liga_imagem'] = $path;
        }

        $ligaEscudo->update($data);

        return redirect()->route('admin.ligas-escudos.index')->with('success', 'Escudo atualizado com sucesso.');
    }

    public function destroy(LigaEscudo $ligaEscudo): RedirectResponse
    {
        if ($ligaEscudo->liga_imagem) {
            Storage::disk('public')->delete($ligaEscudo->liga_imagem);
        }

        $ligaEscudo->delete();

        return redirect()->route('admin.ligas-escudos.index')->with('success', 'Escudo removido com sucesso.');
    }
}
