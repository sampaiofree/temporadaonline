<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscudoClube;
use App\Models\LigaEscudo;
use App\Models\Pais;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EscudoClubeController extends Controller
{
    public function index(Request $request): View
    {
        $paises = Pais::orderBy('nome')->get();
        $ligas = LigaEscudo::orderBy('liga_nome')->get();
        $filters = [
            'search' => (string) $request->query('search', ''),
            'pais_id' => (string) $request->query('pais_id', ''),
            'liga_id' => (string) $request->query('liga_id', ''),
            'created_from' => (string) $request->query('created_from', ''),
            'created_until' => (string) $request->query('created_until', ''),
        ];

        $escudos = $this->applyFilters(
            EscudoClube::with(['pais', 'liga'])->orderBy('clube_nome'),
            $request
        )
            ->paginate()
            ->withQueryString();

        return view('admin.escudos-clubes.index', compact('paises', 'ligas', 'escudos', 'filters'));
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

    public function edit(EscudoClube $escudoClube, Request $request): View
    {
        $paises = Pais::orderBy('nome')->get();
        $ligas = LigaEscudo::orderBy('liga_nome')->get();

        $returnQuery = $request->getQueryString();

        return view('admin.escudos-clubes.edit', compact('escudoClube', 'paises', 'ligas', 'returnQuery'));
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

        return redirect()->route('admin.escudos-clubes.index', $request->query())->with('success', 'Escudo atualizado com sucesso.');
    }

    public function destroy(Request $request, EscudoClube $escudoClube): RedirectResponse
    {
        if ($escudoClube->clube_imagem) {
            Storage::disk('public')->delete($escudoClube->clube_imagem);
        }

        $escudoClube->delete();

        return redirect()->route('admin.escudos-clubes.index', $request->query())->with('success', 'Escudo removido com sucesso.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $deleted = 0;
        $this->applyFilters(EscudoClube::query()->orderBy('id'), $request)
            ->chunkById(100, function ($escudos) use (&$deleted) {
                foreach ($escudos as $escudo) {
                    if ($escudo->clube_imagem) {
                        Storage::disk('public')->delete($escudo->clube_imagem);
                    }

                    $escudo->delete();
                    $deleted++;
                }
            });

        $message = $deleted
            ? "{$deleted} escudo(s) removido(s)."
            : 'Nenhum escudo encontrado para exclusão.';

        return redirect()->route('admin.escudos-clubes.index', $request->query())->with('success', $message);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $query) use ($like) {
                $query->where('clube_nome', 'like', $like)
                    ->orWhereHas('pais', fn (Builder $query) => $query->where('nome', 'like', $like))
                    ->orWhereHas('liga', fn (Builder $query) => $query->where('liga_nome', 'like', $like));
            });
        }

        if ($paisId = $request->query('pais_id')) {
            $query->where('pais_id', (int) $paisId);
        }

        if ($ligaId = $request->query('liga_id')) {
            $query->where('liga_id', (int) $ligaId);
        }

        if ($from = $request->query('created_from')) {
            try {
                $fromDate = Carbon::parse($from);
                $query->whereDate('created_at', '>=', $fromDate->startOfDay());
            } catch (InvalidFormatException) {
                //
            }
        }

        if ($until = $request->query('created_until')) {
            try {
                $untilDate = Carbon::parse($until);
                $query->whereDate('created_at', '<=', $untilDate->endOfDay());
            } catch (InvalidFormatException) {
                //
            }
        }

        return $query;
    }
}
