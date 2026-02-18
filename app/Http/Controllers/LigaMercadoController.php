<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLiga;
use App\Models\Elencopadrao;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaProposta;
use App\Models\PlayerFavorite;
use App\Services\AuctionService;
use App\Services\MarketWindowService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigaMercadoController extends Controller
{
    use ResolvesLiga;

    public function index(Request $request): View
    {
        $liga = $this->resolveUserLiga($request);
        $liga->loadMissing('confederacao:id,nome,jogo_id');
        $userClub = $this->resolveUserClub($request);

        if ((string) ($liga->status ?? '') !== 'ativa') {
            return view('liga_mercado', [
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'saldo_inicial' => (int) ($liga->saldo_inicial ?? 0),
                    'jogo' => $liga->jogo?->nome,
                    'multa_multiplicador' => $liga->multa_multiplicador !== null ? (float) $liga->multa_multiplicador : null,
                ],
                'clube' => null,
                'players' => [],
                'mercadoPayload' => [
                    'players' => [],
                    'closed' => false,
                    'period' => null,
                    'mode' => MarketWindowService::MODE_OPEN,
                    'auction_period' => null,
                    'bid_increment_options' => [],
                    'bid_duration_seconds' => 0,
                    'radar_ids' => [],
                    'propostas_recebidas_count' => 0,
                    'blocked_reason' => 'sem_liga_ativa',
                    'blocked_message' => 'Você precisa estar em uma liga ativa para acessar o mercado.',
                ],
                'appContext' => $this->makeAppContext($liga, null, 'mercado'),
            ]);
        }

        if (! $userClub) {
            return view('liga_mercado', [
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'saldo_inicial' => (int) ($liga->saldo_inicial ?? 0),
                    'jogo' => $liga->jogo?->nome,
                    'multa_multiplicador' => $liga->multa_multiplicador !== null ? (float) $liga->multa_multiplicador : null,
                ],
                'clube' => null,
                'players' => [],
                'mercadoPayload' => [
                    'players' => [],
                    'closed' => false,
                    'period' => null,
                    'mode' => MarketWindowService::MODE_OPEN,
                    'auction_period' => null,
                    'bid_increment_options' => [],
                    'bid_duration_seconds' => 0,
                    'radar_ids' => [],
                    'propostas_recebidas_count' => 0,
                    'blocked_reason' => 'sem_clube',
                    'blocked_message' => 'Você precisa criar um clube para acessar o mercado.',
                ],
                'appContext' => $this->makeAppContext($liga, null, 'mercado'),
            ]);
        }

        /** @var AuctionService $auctionService */
        $auctionService = app(AuctionService::class);
        /** @var MarketWindowService $marketWindowService */
        $marketWindowService = app(MarketWindowService::class);

        if ($liga->confederacao_id) {
            $auctionService->finalizeExpiredAuctions((int) $liga->confederacao_id);
        }

        $marketWindow = $marketWindowService->resolveForLiga($liga);
        $marketMode = (string) ($marketWindow['mode'] ?? MarketWindowService::MODE_OPEN);
        $periodoAtivo = $marketWindow['match_period'] ?? null;
        $periodoLeilaoAtivo = $marketWindow['auction_period'] ?? null;
        $mercadoFechado = $marketMode === MarketWindowService::MODE_CLOSED;
        $mercadoLeilao = $marketMode === MarketWindowService::MODE_AUCTION;
        $confederacaoId = $liga->confederacao_id;

        $elencosQuery = LigaClubeElenco::with(['elencopadrao', 'ligaClube.liga']);

        if ($confederacaoId) {
            $elencosQuery->where('confederacao_id', $confederacaoId);
        } else {
            $elencosQuery->where('liga_id', $liga->id);
        }
        $elencosQuery->where('ativo', true);

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

        $marketJogoId = (int) ($liga->confederacao?->jogo_id ?? $liga->jogo_id);

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
            ->where('jogo_id', $marketJogoId)
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
            ->values();

        if ($mercadoLeilao) {
            $players = $players
                ->filter(fn (array $player) => ($player['club_status'] ?? '') === 'livre')
                ->values();

            $auctionSnapshot = $auctionService->buildAuctionSnapshotForPlayers(
                $liga,
                $players->pluck('elencopadrao_id')->all(),
                $userClub ? (int) $userClub->id : null,
            );

            $players = $players
                ->map(function (array $player) use ($auctionSnapshot) {
                    $playerId = (int) ($player['elencopadrao_id'] ?? 0);
                    $baseValue = (int) ($player['value_eur'] ?? 0);
                    $auction = $auctionSnapshot[$playerId] ?? null;

                    $player['can_buy'] = false;
                    $player['can_multa'] = false;
                    $player['auction'] = [
                        'enabled' => true,
                        'status' => (string) ($auction['status'] ?? 'aberto'),
                        'has_bid' => (bool) ($auction['has_bid'] ?? false),
                        'base_value_eur' => (int) ($auction['base_value_eur'] ?? $baseValue),
                        'current_bid_eur' => (int) ($auction['current_bid_eur'] ?? $baseValue),
                        'leader_club_id' => isset($auction['leader_club_id']) ? (int) $auction['leader_club_id'] : null,
                        'leader_club_name' => $auction['leader_club_name'] ?? null,
                        'expires_at' => $auction['expires_at'] ?? null,
                        'seconds_remaining' => isset($auction['seconds_remaining']) ? (int) $auction['seconds_remaining'] : null,
                        'is_leader' => (bool) ($auction['is_leader'] ?? false),
                        'next_min_bid_eur' => (int) ($auction['next_min_bid_eur'] ?? $baseValue),
                    ];

                    return $player;
                })
                ->values();
        }

        $players = $players->all();

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
            'mode' => $marketMode,
            'auction_period' => $periodoLeilaoAtivo,
            'bid_increment_options' => $auctionService->getBidIncrementOptions(),
            'bid_duration_seconds' => $auctionService->getBidDurationSeconds(),
            'radar_ids' => $favoriteIds,
            'propostas_recebidas_count' => $userClub
                ? (int) LigaProposta::query()
                    ->where('clube_origem_id', $userClub->id)
                    ->where('status', 'aberta')
                    ->count()
                : 0,
            'blocked_reason' => null,
            'blocked_message' => null,
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

        if ((string) ($liga->status ?? '') !== 'ativa') {
            abort(403, 'Mercado disponível apenas para ligas ativas.');
        }

        if (! $userClub) {
            abort(403, 'Crie um clube antes de acessar propostas.');
        }

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
