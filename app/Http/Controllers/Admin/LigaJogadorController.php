<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaJogador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaJogadorController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'liga_id' => (string) $request->query('liga_id', ''),
        ];

        $ligas = Liga::orderBy('nome')->get();

        $ligaJogadores = $this->applyFilters(
            LigaJogador::with(['user', 'liga'])->orderByDesc('created_at'),
            $filters
        )
            ->paginate(15)
            ->withQueryString();

        $clubesByKey = LigaClube::query()
            ->whereIn('liga_id', $ligaJogadores->getCollection()->pluck('liga_id')->unique()->all())
            ->whereIn('user_id', $ligaJogadores->getCollection()->pluck('user_id')->unique()->all())
            ->get(['liga_id', 'user_id', 'nome'])
            ->keyBy(fn (LigaClube $clube) => $this->clubKey($clube->liga_id, $clube->user_id));

        $ligaJogadores->getCollection()->transform(function (LigaJogador $entry) use ($clubesByKey): LigaJogador {
            $entry->setAttribute(
                'club_name',
                $clubesByKey->get($this->clubKey($entry->liga_id, $entry->user_id))?->nome,
            );

            return $entry;
        });

        return view('admin.ligas-usuarios.index', [
            'ligaJogadores' => $ligaJogadores,
            'ligas' => $ligas,
            'filters' => $filters,
        ]);
    }

    public function destroy(Request $request, LigaJogador $ligaJogador): RedirectResponse
    {
        LigaClube::query()
            ->where('user_id', $ligaJogador->user_id)
            ->where('liga_id', $ligaJogador->liga_id)
            ->each(fn (LigaClube $clube) => $clube->delete());

        $ligaJogador->delete();

        return redirect()->route('admin.ligas-usuarios.index', $request->query())
            ->with('success', 'Usuário removido da liga e dados relacionados deletados.');
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if ($filters['search'] !== '') {
            $like = '%'.strtolower($filters['search']).'%';

            $query->whereHas('user', function ($builder) use ($like) {
                $builder->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(nickname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }

        if ($filters['liga_id'] !== '') {
            $query->where('liga_id', (int) $filters['liga_id']);
        }

        return $query;
    }

    private function clubKey(int|string|null $ligaId, int|string|null $userId): string
    {
        return sprintf('%s:%s', (string) $ligaId, (string) $userId);
    }
}
