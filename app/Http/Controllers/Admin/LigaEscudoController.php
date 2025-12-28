<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class LigaEscudoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'pais_id' => (string) $request->query('pais_id', ''),
            'created_from' => (string) $request->query('created_from', ''),
            'created_until' => (string) $request->query('created_until', ''),
        ];

        $paises = Pais::orderBy('nome')->get();
        $ligas = $this->applyFilters(LigaEscudo::with('pais')->orderBy('liga_nome'), $request)
            ->paginate()
            ->withQueryString();

        return view('admin.ligas-escudos.index', [
            'ligas' => $ligas,
            'paises' => $paises,
            'filters' => $filters,
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

    public function edit(LigaEscudo $ligaEscudo, Request $request): View
    {
        $paises = Pais::orderBy('nome')->get();

        return view('admin.ligas-escudos.edit', [
            'ligaEscudo' => $ligaEscudo,
            'paises' => $paises,
            'returnQuery' => $request->getQueryString(),
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

        return redirect()->route('admin.ligas-escudos.index', $request->query())->with('success', 'Escudo atualizado com sucesso.');
    }

    public function destroy(Request $request, LigaEscudo $ligaEscudo): RedirectResponse
    {
        if ($ligaEscudo->liga_imagem) {
            Storage::disk('public')->delete($ligaEscudo->liga_imagem);
        }

        $ligaEscudo->delete();

        return redirect()->route('admin.ligas-escudos.index', $request->query())->with('success', 'Escudo removido com sucesso.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $deleted = 0;
        $this->applyFilters(LigaEscudo::query()->orderBy('id'), $request)
            ->chunkById(100, function ($ligas) use (&$deleted) {
                foreach ($ligas as $liga) {
                    if ($liga->liga_imagem) {
                        Storage::disk('public')->delete($liga->liga_imagem);
                    }

                    $liga->delete();
                    $deleted++;
                }
            });

        $message = $deleted
            ? "{$deleted} escudo(s) removido(s)."
            : 'Nenhum escudo encontrado para exclusão.';

        return redirect()->route('admin.ligas-escudos.index', $request->query())->with('success', $message);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $builder) use ($like) {
                $builder->where('liga_nome', 'like', $like)
                    ->orWhereHas('pais', fn (Builder $builder) => $builder->where('nome', 'like', $like));
            });
        }

        if ($paisId = $request->query('pais_id')) {
            $query->where('pais_id', (int) $paisId);
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
