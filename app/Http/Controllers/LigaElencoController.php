<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Elencopadrao;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\PartidaDesempenho;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LigaElencoController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $clube = $this->resolveUserClub($request);

        $stats = PartidaDesempenho::query()
            ->select([
                'partida_desempenhos.elencopadrao_id',
                DB::raw('AVG(partida_desempenhos.nota) as nota_media'),
                DB::raw('SUM(partida_desempenhos.gols) as gols_total'),
                DB::raw('SUM(partida_desempenhos.assistencias) as assistencias_total'),
                DB::raw('COUNT(partida_desempenhos.id) as jogos'),
            ])
            ->join('partidas', 'partidas.id', '=', 'partida_desempenhos.partida_id')
            ->where('partidas.liga_id', $liga->id)
            ->groupBy('partida_desempenhos.elencopadrao_id')
            ->get();

        $playerIds = $stats->pluck('elencopadrao_id')->filter()->values();

        if ($playerIds->isEmpty()) {
            return $this->renderView($liga, $clube, []);
        }

        $players = Elencopadrao::query()
            ->whereIn('id', $playerIds)
            ->get([
                'id',
                'short_name',
                'long_name',
                'player_positions',
                'overall',
                'player_face_url',
            ])
            ->keyBy('id');

        $rosterEntries = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->whereIn('elencopadrao_id', $playerIds)
            ->get(['elencopadrao_id', 'liga_clube_id'])
            ->keyBy('elencopadrao_id');

        $missingIds = $playerIds->diff($rosterEntries->keys());
        $fallbackClubIds = $missingIds->isNotEmpty()
            ? $this->resolveLatestClubIds($liga->id, $missingIds)
            : collect();

        $clubIds = $rosterEntries->pluck('liga_clube_id')
            ->merge($fallbackClubIds->values())
            ->filter()
            ->unique()
            ->values();

        $clubs = LigaClube::query()
            ->with('escudo')
            ->whereIn('id', $clubIds)
            ->get(['id', 'nome', 'escudo_clube_id'])
            ->keyBy('id');

        $elenco = $stats->map(function ($item) use ($players, $rosterEntries, $fallbackClubIds, $clubs) {
            $player = $players->get($item->elencopadrao_id);
            if (! $player) {
                return null;
            }

            $clubId = $rosterEntries->get($item->elencopadrao_id)?->liga_clube_id
                ?? $fallbackClubIds->get($item->elencopadrao_id);

            $club = $clubId ? $clubs->get($clubId) : null;
            $clubEscudo = $club?->escudo?->clube_imagem;

            return [
                'player_id' => $player->id,
                'nome' => $player->short_name ?? $player->long_name ?? '—',
                'posicao' => $this->resolvePrimaryPosition($player->player_positions),
                'overall' => $player->overall,
                'foto_url' => $player->player_face_url,
                'nota_media' => (float) $item->nota_media,
                'jogos' => (int) $item->jogos,
                'gols' => (int) $item->gols_total,
                'assistencias' => (int) $item->assistencias_total,
                'clube' => $club ? [
                    'id' => $club->id,
                    'nome' => $club->nome,
                    'escudo_url' => $this->resolveEscudoUrl($clubEscudo),
                ] : null,
            ];
        })
            ->filter()
            ->values()
            ->sort(function ($a, $b) {
                if ($a['nota_media'] !== $b['nota_media']) {
                    return $b['nota_media'] <=> $a['nota_media'];
                }
                if ($a['jogos'] !== $b['jogos']) {
                    return $b['jogos'] <=> $a['jogos'];
                }
                if ($a['gols'] !== $b['gols']) {
                    return $b['gols'] <=> $a['gols'];
                }
                if ($a['assistencias'] !== $b['assistencias']) {
                    return $b['assistencias'] <=> $a['assistencias'];
                }

                $overallA = (int) ($a['overall'] ?? 0);
                $overallB = (int) ($b['overall'] ?? 0);
                if ($overallA !== $overallB) {
                    return $overallB <=> $overallA;
                }

                return strcasecmp($a['nome'], $b['nome']);
            })
            ->values()
            ->all();

        return $this->renderView($liga, $clube, $elenco);
    }

    private function renderView($liga, $clube, array $elenco): View
    {
        return view('liga_elenco', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            ],
            'elenco' => $elenco,
            'appContext' => $this->makeAppContext($liga, $clube, 'liga'),
        ]);
    }

    private function resolvePrimaryPosition(?string $positions): string
    {
        if (! $positions) {
            return '—';
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $positions))));
        return $parts[0] ?? '—';
    }

    private function resolveLatestClubIds(int $ligaId, Collection $playerIds): Collection
    {
        $rows = PartidaDesempenho::query()
            ->select(['partida_desempenhos.elencopadrao_id', 'partida_desempenhos.liga_clube_id'])
            ->join('partidas', 'partidas.id', '=', 'partida_desempenhos.partida_id')
            ->where('partidas.liga_id', $ligaId)
            ->whereIn('partida_desempenhos.elencopadrao_id', $playerIds->all())
            ->orderByDesc('partida_desempenhos.created_at')
            ->get();

        $fallback = [];

        foreach ($rows as $row) {
            if (! isset($fallback[$row->elencopadrao_id])) {
                $fallback[$row->elencopadrao_id] = $row->liga_clube_id;
            }
        }

        return collect($fallback);
    }

    private function resolveEscudoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
