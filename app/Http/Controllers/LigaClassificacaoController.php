<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\LigaClube;
use App\Models\Partida;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LigaClassificacaoController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);

        $clube = $this->resolveUserClub($request);

        $clubs = LigaClube::query()
            ->where('liga_id', $liga->id)
            ->with('escudo')
            ->orderBy('nome')
            ->get();

        $stats = $this->initializeStats($clubs);

        $partidas = Partida::query()
            ->where('liga_id', $liga->id)
            ->whereIn('estado', ['placar_registrado', 'placar_confirmado', 'wo'])
            ->get(['mandante_id', 'visitante_id', 'placar_mandante', 'placar_visitante']);

        $this->aggregateMatches($stats, $partidas);

        $classification = $this->buildRanking($stats);

        return view('liga_classificacao', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            ],
            'classification' => $classification,
            'appContext' => $this->makeAppContext($liga, $clube, 'tabela'),
        ]);
    }

    /**
     * @param Collection<LigaClube> $clubs
     */
    private function initializeStats(Collection $clubs): array
    {
        return $clubs->values()->mapWithKeys(function (LigaClube $clube, $index) {
            return [
                $clube->id => [
                    'clube_id' => $clube->id,
                    'clube_nome' => $clube->nome,
                    'pontos' => 0,
                    'vitorias' => 0,
                    'empates' => 0,
                    'derrotas' => 0,
                    'gols_marcados' => 0,
                    'gols_sofridos' => 0,
                    'saldo_gols' => 0,
                    'partidas_jogadas' => 0,
                    'clube_escudo_url' => $clube->escudo?->clube_imagem,
                    'club_order' => $index,
                ],
            ];
        })->all();
    }

    /**
     * @param array<int, array<string, mixed>> $stats
     * @param Collection<int, Partida> $partidas
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
        $ranking = collect($stats)->map(function ($item) {
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
                'clube_escudo_url' => $item['clube_escudo_url'] ?? null,
            ];
        });
    }
}
