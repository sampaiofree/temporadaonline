<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscudoClube;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeFinanceiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'liga_id' => (string) $request->query('liga_id', ''),
        ];

        $ligas = Liga::orderBy('nome')->get();

        $clubes = LigaClube::with(['user', 'liga', 'financeiro', 'escudo'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $like = '%'.strtolower($filters['search']).'%';

                $query->where(function ($builder) use ($like) {
                    $builder->whereRaw('LOWER(liga_clubes.nome) LIKE ?', [$like])
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery->whereRaw('LOWER(name) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(nickname) LIKE ?', [$like]);
                        });
                });
            })
            ->when($filters['liga_id'] !== '', function ($query) use ($filters) {
                $query->where('liga_id', (int) $filters['liga_id']);
            })
            ->orderBy('nome')
            ->paginate()
            ->withQueryString();

        return view('admin.clubes.index', [
            'clubes' => $clubes,
            'ligas' => $ligas,
            'filters' => $filters,
        ]);
    }

    public function edit(LigaClube $clube, Request $request): View
    {
        $escudos = EscudoClube::orderBy('clube_nome')->get();
        $selectedEscudoId = $clube->escudo_clube_id;
        $usedEscudos = LigaClube::query()
            ->where('liga_id', $clube->liga_id)
            ->whereNotNull('escudo_clube_id')
            ->where('id', '<>', $clube->id)
            ->pluck('escudo_clube_id')
            ->all();
        $saldoAtual = (int) ($clube->financeiro?->saldo ?? 0);

        return view('admin.clubes.edit', [
            'clube' => $clube->loadMissing(['user', 'liga', 'financeiro', 'escudo']),
            'escudos' => $escudos,
            'selectedEscudoId' => $selectedEscudoId,
            'usedEscudos' => $usedEscudos,
            'saldoAtual' => $saldoAtual,
            'returnQuery' => $request->getQueryString(),
        ]);
    }

    public function update(Request $request, LigaClube $clube): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:150',
            'escudo_id' => 'nullable|exists:escudos_clubes,id',
            'saldo' => 'required|integer|min:0',
        ]);

        $escudoId = $validated['escudo_id'] ?? null;
        if ($escudoId) {
            $escudoInUse = LigaClube::query()
                ->where('liga_id', $clube->liga_id)
                ->where('escudo_clube_id', $escudoId)
                ->where('id', '<>', $clube->id)
                ->exists();

            if ($escudoInUse) {
                return back()
                    ->withErrors(['escudo_id' => 'Este escudo já está em uso por outro clube nesta liga.'])
                    ->withInput();
            }
        }

        $escudo = $escudoId
            ? EscudoClube::query()->find($escudoId)
            : null;

        $clube->update([
            'nome' => trim($validated['nome']),
            'escudo_clube_id' => $escudo?->id,
        ]);

        LigaClubeFinanceiro::updateOrCreate(
            [
                'liga_id' => $clube->liga_id,
                'clube_id' => $clube->id,
            ],
            [
                'saldo' => $validated['saldo'],
            ],
        );

        return redirect()->route('admin.clubes.index', $request->query())->with('success', 'Clube atualizado com sucesso.');
    }

    public function destroy(Request $request, LigaClube $clube): RedirectResponse
    {
        $clube->delete();

        return redirect()->route('admin.clubes.index', $request->query())->with('success', 'Clube removido com sucesso.');
    }
}
