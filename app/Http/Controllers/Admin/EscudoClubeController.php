<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscudoClube;
use App\Models\LigaEscudo;
use App\Models\Pais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EscudoClubeController extends Controller
{
    public function index(): View
    {
        $paises = Pais::orderBy('nome')->get();
        $ligas = LigaEscudo::orderBy('liga_nome')->get();
        $escudos = EscudoClube::with(['pais', 'liga'])->orderBy('clube_nome')->get();

        return view('admin.escudos-clubes.index', compact('paises', 'ligas', 'escudos'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'default_pais_id' => 'nullable|exists:paises,id',
            'default_liga_id' => 'nullable|exists:ligas_escudos,id',
            'uploads' => 'required|array|min:1',
            'uploads.*.clube_nome' => 'required|string|max:150',
            'uploads.*.pais_id' => 'required|exists:paises,id',
            'uploads.*.liga_id' => 'required|exists:ligas_escudos,id',
            'uploads.*.clube_imagem' => 'required|image:allow_svg|max:4096',
        ]);

        $created = 0;
        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.clube_imagem");

            if (! $file) {
                continue;
            }

            $path = $file->store('escudos-clubes', 'public');

            EscudoClube::create([
                'clube_nome' => trim($upload['clube_nome']),
                'pais_id' => $upload['pais_id'],
                'liga_id' => $upload['liga_id'],
                'clube_imagem' => $path,
            ]);

            $created++;
        }

        $request->session()->flash('success', "{$created} escudo(s) de clube cadastrado(s).");

        if ($request->wantsJson()) {
            return response()->json(['created' => $created], 201);
        }

        return redirect()->route('admin.escudos-clubes.index');
    }

    public function edit(EscudoClube $escudoClube): View
    {
        $paises = Pais::orderBy('nome')->get();
        $ligas = LigaEscudo::orderBy('liga_nome')->get();

        return view('admin.escudos-clubes.edit', compact('escudoClube', 'paises', 'ligas'));
    }

    public function update(Request $request, EscudoClube $escudoClube): RedirectResponse
    {
        $validated = $request->validate([
            'clube_nome' => 'required|string|max:150',
            'pais_id' => 'required|exists:paises,id',
            'liga_id' => 'required|exists:ligas_escudos,id',
            'imagem' => 'nullable|image:allow_svg|max:4096',
        ]);

        $data = [
            'clube_nome' => trim($validated['clube_nome']),
            'pais_id' => $validated['pais_id'],
            'liga_id' => $validated['liga_id'],
        ];

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('escudos-clubes', 'public');
            if ($escudoClube->clube_imagem) {
                Storage::disk('public')->delete($escudoClube->clube_imagem);
            }
            $data['clube_imagem'] = $path;
        }

        $escudoClube->update($data);

        return redirect()->route('admin.escudos-clubes.index')->with('success', 'Escudo atualizado com sucesso.');
    }

    public function destroy(EscudoClube $escudoClube): RedirectResponse
    {
        if ($escudoClube->clube_imagem) {
            Storage::disk('public')->delete($escudoClube->clube_imagem);
        }

        $escudoClube->delete();

        return redirect()->route('admin.escudos-clubes.index')->with('success', 'Escudo removido com sucesso.');
    }
}
