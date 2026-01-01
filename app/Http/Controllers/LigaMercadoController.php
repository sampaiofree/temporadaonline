<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Elencopadrao;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaPeriodo;
use App\Models\LigaProposta;
use App\Models\PlayerFavorite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaMercadoController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $this->resolveUserClub($request);
        $periodoAtivo = LigaPeriodo::activeRangeForLiga($liga);
        $mercadoFechado = $periodoAtivo !== null;
        $confederacaoId = $liga->confederacao_id;

        $elencosQuery = LigaClubeElenco::with(['elencopadrao', 'ligaClube.liga']);

        if ($confederacaoId) {
            $elencosQuery->where('confederacao_id', $confederacaoId);
        } else {
            $elencosQuery->where('liga_id', $liga->id);
        }

        $elencos = $elencosQuery->get()->keyBy('elencopadrao_id');

        $walletSaldo = 0;
        $salaryPerRound = 0;

        if ($userClub) {
            $walletSaldo = (int) LigaClubeFinanceiro::query()
                ->where('liga_id', $liga->id)
                ->where('clube_id', $userClub->id)
                ->value('saldo');

            $salaryPerRound = (int) LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $userClub->id)
                ->where('ativo', true)
                ->sum('wage_eur');
        }

        $players = Elencopadrao::query()
            ->select([
                'id',
                'short_name',
                'long_name',
                'player_positions',
                'overall',
                'value_eur',
                'wage_eur',
                'player_face_url',
            ])
            ->where('jogo_id', $liga->jogo_id)
            ->orderByDesc('overall')
            ->get()
            ->map(function (Elencopadrao $player) use ($elencos, $userClub) {
                $entry = $elencos->get($player->id);
                $club = $entry?->ligaClube;
                $clubLiga = $club?->liga;

                $clubStatus = 'livre';
                $canBuy = ! $entry;
                $canMulta = false;

                if ($entry && $club) {
                    $clubStatus = $userClub && $club->id === $userClub->id ? 'meu' : 'outro';
                    $canBuy = $clubStatus === 'outro';
                    $canMulta = $clubStatus === 'outro';
                }

                return [
                    'elencopadrao_id' => $player->id,
                    'short_name' => $player->short_name,
                    'long_name' => $player->long_name,
                    'player_positions' => $player->player_positions,
                    'overall' => $player->overall,
                    'value_eur' => $player->value_eur,
                    'wage_eur' => $player->wage_eur,
                    'club_status' => $clubStatus,
                    'club_name' => $club?->nome,
                    'liga_nome' => $clubLiga?->nome,
                    'multa_multiplicador' => $clubLiga?->multa_multiplicador !== null
                        ? (float) $clubLiga->multa_multiplicador
                        : null,
                    'club_id' => $club?->id,
                    'is_free_agent' => $clubStatus === 'livre',
                    'can_buy' => $canBuy,
                    'can_multa' => $canMulta,
                    'entry_value_eur' => $entry?->value_eur,
                    'player_face_url' => $player->player_face_url,
                ];
            })
            ->values()
            ->all();

        $favoriteQuery = PlayerFavorite::query()
            ->where('user_id', $request->user()->id);

        if ($confederacaoId) {
            $favoriteQuery->where('confederacao_id', $confederacaoId);
        } else {
            $favoriteQuery->where('liga_id', $liga->id);
        }

        $favoriteIds = $favoriteQuery
            ->pluck('elencopadrao_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $mercadoPayload = [
            'players' => $players,
            'closed' => $mercadoFechado,
            'period' => $periodoAtivo,
            'radar_ids' => $favoriteIds,
            'propostas_recebidas_count' => $userClub
                ? (int) LigaProposta::query()
                    ->where('clube_origem_id', $userClub->id)
                    ->where('status', 'aberta')
                    ->count()
                : 0,
        ];

        return view('liga_mercado', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'saldo_inicial' => (int) ($liga->saldo_inicial ?? 0),
                'jogo' => $liga->jogo?->nome,
                'multa_multiplicador' => $liga->multa_multiplicador !== null ? (float) $liga->multa_multiplicador : null,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
                'saldo' => $walletSaldo,
                'salary_per_round' => $salaryPerRound,
            ] : null,
            'players' => $players,
            'mercadoPayload' => $mercadoPayload,
            'appContext' => $this->makeAppContext($liga, $userClub, 'mercado'),
        ]);
    }

    public function propostas(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $userClub = $this->resolveUserClub($request);

        return view('liga_mercado_propostas', [
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
            ] : null,
            'appContext' => $this->makeAppContext($liga, $userClub, 'mercado'),
        ]);
    }
}
