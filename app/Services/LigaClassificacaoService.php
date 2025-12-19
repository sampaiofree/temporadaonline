<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\User;
use Illuminate\Support\Collection;

class LigaClassificacaoService
{
    /**
     * Retorna a classificação completa de uma liga.
     */
    public function rankingForLiga(Liga $liga): Collection
    {
        $stats = $this->computeStats($liga);

        return $this->buildRanking($stats);
    }

    /**
     * Informa se o usuário já soma ao menos 1 ponto em alguma liga.
     */
    public function userHasPoints(User $user): bool
    {
        $clubs = $user->clubesLiga()->with('liga')->get();
        $leagueCache = [];

        foreach ($clubs->groupBy('liga_id') as $ligaId => $group) {
            $liga = $group->first()->liga;

            if (! $liga) {
                continue;
            }

            if (! isset($leagueCache[$ligaId])) {
                $leagueCache[$ligaId] = $this->buildRanking($this->computeStats($liga));
            }

            foreach ($group as $club) {
                $stats = $leagueCache[$ligaId]->firstWhere('clube_id', $club->id);

                if ($stats && ($stats['pontos'] ?? 0) >= 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calcula as estatísticas brutas para cada clube da liga.
     *
     * @return array<int, array<string, mixed>>
     */
    private function computeStats(Liga $liga): array
    {
        $clubs = $liga->clubes()->orderBy('nome')->get(['id', 'nome']);

        $stats = $clubs->mapWithKeys(function (LigaClube $club, $index) {
            return [
                $club->id => [
                    'clube_id' => $club->id,
                    'clube_nome' => $club->nome,
                    'pontos' => 0,
                    'vitorias' => 0,
                    'empates' => 0,
                    'derrotas' => 0,
                    'gols_marcados' => 0,
                    'gols_sofridos' => 0,
                    'saldo_gols' => 0,
                    'partidas_jogadas' => 0,
                    'club_order' => $index,
                ],
            ];
        })->all();

        $partidas = Partida::query()
            ->where('liga_id', $liga->id)
            ->whereIn('estado', ['placar_registrado', 'placar_confirmado', 'wo'])
            ->get(['mandante_id', 'visitante_id', 'placar_mandante', 'placar_visitante']);

        $this->aggregateMatches($stats, $partidas);

        return $stats;
    }

    /**
     * @param array<int, array<string, mixed>> $stats
     */
    private function aggregateMatches(array &$stats, Collection $partidas): void
    {
        foreach ($partidas as $partida) {
            if (! isset($stats[$partida->mandante_id], $stats[$partida->visitante_id])) {
                continue;
            }

            $mandanteScore = (int) ($partida->placar_mandante ?? 0);
            $visitanteScore = (int) ($partida->placar_visitante ?? 0);

            $mandante = &$stats[$partida->mandante_id];
            $visitante = &$stats[$partida->visitante_id];

            $mandante['partidas_jogadas']++;
            $visitante['partidas_jogadas']++;

            $mandante['gols_marcados'] += $mandanteScore;
            $mandante['gols_sofridos'] += $visitanteScore;
            $visitante['gols_marcados'] += $visitanteScore;
            $visitante['gols_sofridos'] += $mandanteScore;

            if ($mandanteScore > $visitanteScore) {
                $mandante['vitorias']++;
                $mandante['pontos'] += 3;
                $visitante['derrotas']++;
            } elseif ($mandanteScore < $visitanteScore) {
                $visitante['vitorias']++;
                $visitante['pontos'] += 3;
                $mandante['derrotas']++;
            } else {
                $mandante['empates']++;
                $visitante['empates']++;
                $mandante['pontos']++;
                $visitante['pontos']++;
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $stats
     */
    private function buildRanking(array $stats): Collection
    {
        $ranking = collect($stats)
            ->map(function ($item) {
                $item['saldo_gols'] = $item['gols_marcados'] - $item['gols_sofridos'];
                return $item;
            })
            ->values()
            ->sort(function ($a, $b) {
                if ($a['pontos'] !== $b['pontos']) {
                    return $b['pontos'] <=> $a['pontos'];
                }

                if ($a['vitorias'] !== $b['vitorias']) {
                    return $b['vitorias'] <=> $a['vitorias'];
                }

                return $a['club_order'] <=> $b['club_order'];
            })
            ->values();

        return $ranking->map(function ($item, $index) {
            return [
                'posicao' => $index + 1,
                'clube_id' => $item['clube_id'],
                'clube_nome' => $item['clube_nome'],
                'pontos' => $item['pontos'],
                'vitorias' => $item['vitorias'],
                'empates' => $item['empates'],
                'derrotas' => $item['derrotas'],
                'gols_marcados' => $item['gols_marcados'],
                'gols_sofridos' => $item['gols_sofridos'],
                'saldo_gols' => $item['saldo_gols'],
                'partidas_jogadas' => $item['partidas_jogadas'],
                'club_order' => $item['club_order'],
            ];
        });
    }
}
