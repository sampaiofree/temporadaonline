<?php

namespace App\Services;

use App\Models\LigaClube;
use App\Models\LigaClubeAjusteSalarial;
use App\Models\LigaClubeVendaMercado;
use App\Models\LigaProposta;
use App\Models\LigaTransferencia;
use App\Models\Partida;
use App\Models\PartidaAvaliacao;
use App\Models\PartidaDesempenho;
use App\Models\PartidaEvento;
use Illuminate\Support\Facades\Schema;

class ConquistaProgressService
{
    /**
     * @return array<string, int|float>
     */
    public function emptyProgress(): array
    {
        return [
            'gols' => 0,
            'assistencias' => 0,
            'quantidade_jogos' => 0,
            'skill_rating' => 0,
            'score' => 5.0,
            'n_gols_sofridos' => 0,
            'n_vitorias' => 0,
            'n_hat_trick' => 0,
            'agendar_partidas' => 0,
            'enviar_sumula' => 0,
            'avaliacoes' => 0,
            'ajuste_salarial' => 0,
            'venda_mercado' => 0,
            'compra_mercado' => 0,
            'negociacoes_enviadas' => 0,
            'negociacoes_recebidas' => 0,
            'partidas_sem_levar_gol' => 0,
            'vitorias_por_3_gols_ou_mais_de_diferenca' => 0,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    public function progressForConfederacao(int $userId, int $confederacaoId): array
    {
        $progress = $this->emptyProgress();

        if ($userId <= 0 || $confederacaoId <= 0) {
            return $progress;
        }

        $clubIds = LigaClube::query()
            ->where('user_id', $userId)
            ->where('confederacao_id', $confederacaoId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($clubIds === []) {
            return $progress;
        }

        $clubIdMap = array_fill_keys($clubIds, true);
        $states = ['placar_registrado', 'placar_confirmado', 'wo'];

        $matches = Partida::query()
            ->select(['id', 'mandante_id', 'visitante_id', 'placar_mandante', 'placar_visitante'])
            ->whereIn('estado', $states)
            ->whereNotNull('placar_mandante')
            ->whereNotNull('placar_visitante')
            ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $confederacaoId))
            ->where(function ($query) use ($clubIds): void {
                $query->whereIn('mandante_id', $clubIds)
                    ->orWhereIn('visitante_id', $clubIds);
            })
            ->get();

        $wins = 0;
        $goalsAgainst = 0;
        $matchesPlayed = 0;
        $cleanSheetMatches = 0;
        $winsByThreeOrMoreGoals = 0;

        foreach ($matches as $match) {
            $mandanteId = (int) $match->mandante_id;
            $visitanteId = (int) $match->visitante_id;
            $mandanteGoals = (int) ($match->placar_mandante ?? 0);
            $visitanteGoals = (int) ($match->placar_visitante ?? 0);

            if (isset($clubIdMap[$mandanteId])) {
                $matchesPlayed++;
                $goalsAgainst += $visitanteGoals;
                if ($visitanteGoals === 0) {
                    $cleanSheetMatches++;
                }
                if ($mandanteGoals > $visitanteGoals) {
                    $wins++;
                    if (($mandanteGoals - $visitanteGoals) >= 3) {
                        $winsByThreeOrMoreGoals++;
                    }
                }
            }

            if (isset($clubIdMap[$visitanteId])) {
                $matchesPlayed++;
                $goalsAgainst += $mandanteGoals;
                if ($mandanteGoals === 0) {
                    $cleanSheetMatches++;
                }
                if ($visitanteGoals > $mandanteGoals) {
                    $wins++;
                    if (($visitanteGoals - $mandanteGoals) >= 3) {
                        $winsByThreeOrMoreGoals++;
                    }
                }
            }
        }

        $desempenhosBaseQuery = PartidaDesempenho::query()
            ->whereIn('liga_clube_id', $clubIds)
            ->whereHas('partida', function ($query) use ($confederacaoId, $states): void {
                $query->whereIn('estado', $states)
                    ->whereHas('liga', fn ($ligaQuery) => $ligaQuery->where('confederacao_id', $confederacaoId));
            });

        $desempenhos = (clone $desempenhosBaseQuery)
            ->selectRaw('COALESCE(SUM(gols), 0) as total_gols, COALESCE(SUM(assistencias), 0) as total_assistencias')
            ->first();

        $hatTricks = (clone $desempenhosBaseQuery)
            ->where('gols', '>=', 3)
            ->count();

        $avaliacaoRecebida = PartidaAvaliacao::query()
            ->where('avaliado_user_id', $userId)
            ->whereHas('partida', fn ($query) => $query->whereHas('liga', fn ($ligaQuery) => $ligaQuery->where('confederacao_id', $confederacaoId)))
            ->avg('nota');

        $agendamentos = PartidaEvento::query()
            ->where('tipo', 'confirmacao_horario')
            ->where('user_id', $userId)
            ->whereHas('partida', fn ($query) => $query->whereHas('liga', fn ($ligaQuery) => $ligaQuery->where('confederacao_id', $confederacaoId)))
            ->distinct('partida_id')
            ->count('partida_id');

        $sumulas = Partida::query()
            ->where('placar_registrado_por', $userId)
            ->whereHas('liga', fn ($query) => $query->where('confederacao_id', $confederacaoId))
            ->count();

        $avaliacoesFeitas = PartidaAvaliacao::query()
            ->where('avaliador_user_id', $userId)
            ->whereHas('partida', fn ($query) => $query->whereHas('liga', fn ($ligaQuery) => $ligaQuery->where('confederacao_id', $confederacaoId)))
            ->count();

        $ajustesSalariais = LigaClubeAjusteSalarial::query()
            ->where('user_id', $userId)
            ->where('confederacao_id', $confederacaoId)
            ->count();

        $vendasMercado = Schema::hasTable('liga_clube_vendas_mercado')
            ? LigaClubeVendaMercado::query()
                ->where('user_id', $userId)
                ->where('confederacao_id', $confederacaoId)
                ->count()
            : 0;

        $comprasMercado = LigaTransferencia::query()
            ->where('confederacao_id', $confederacaoId)
            ->where('tipo', 'jogador_livre')
            ->whereIn('clube_destino_id', $clubIds)
            ->count();

        $negociacoesEnviadas = LigaProposta::query()
            ->where('confederacao_id', $confederacaoId)
            ->whereIn('clube_destino_id', $clubIds)
            ->count();

        $negociacoesRecebidas = LigaProposta::query()
            ->where('confederacao_id', $confederacaoId)
            ->whereIn('clube_origem_id', $clubIds)
            ->count();

        $progress['gols'] = (int) ($desempenhos?->total_gols ?? 0);
        $progress['assistencias'] = (int) ($desempenhos?->total_assistencias ?? 0);
        $progress['quantidade_jogos'] = (int) $matchesPlayed;
        $progress['n_vitorias'] = (int) $wins;
        $progress['n_gols_sofridos'] = (int) $goalsAgainst;
        $progress['n_hat_trick'] = (int) $hatTricks;
        $progress['skill_rating'] = $matchesPlayed > 0
            ? (int) max(0, min(100, round(($wins / $matchesPlayed) * 100)))
            : 0;
        $progress['score'] = $avaliacaoRecebida !== null
            ? (float) max(1, min(5, round((float) $avaliacaoRecebida, 1)))
            : 5.0;
        $progress['agendar_partidas'] = (int) $agendamentos;
        $progress['enviar_sumula'] = (int) $sumulas;
        $progress['avaliacoes'] = (int) $avaliacoesFeitas;
        $progress['ajuste_salarial'] = (int) $ajustesSalariais;
        $progress['venda_mercado'] = (int) $vendasMercado;
        $progress['compra_mercado'] = (int) $comprasMercado;
        $progress['negociacoes_enviadas'] = (int) $negociacoesEnviadas;
        $progress['negociacoes_recebidas'] = (int) $negociacoesRecebidas;
        $progress['partidas_sem_levar_gol'] = (int) $cleanSheetMatches;
        $progress['vitorias_por_3_gols_ou_mais_de_diferenca'] = (int) $winsByThreeOrMoreGoals;

        return $progress;
    }
}
