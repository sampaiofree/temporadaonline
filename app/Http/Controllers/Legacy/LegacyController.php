<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeConquista;
use App\Models\LigaClubeFinanceiro;
use App\Models\LigaClubePatrocinio;
use App\Models\LigaPeriodo;
use App\Models\LigaProposta;
use App\Models\LigaTransferencia;
use App\Models\Partida;
use App\Models\PartidaAvaliacao;
use App\Models\PartidaDesempenho;
use App\Models\PartidaFolhaPagamento;
use App\Models\PlayerFavorite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegacyController extends Controller
{
    private const MATCH_WINNER_PRIZE = 750_000;
    private const MATCH_LOSER_PRIZE = 50_000;
    private const MATCH_DRAW_PRIZE = 300_000;

    public function index(Request $request): View
    {
        $user = $request->user();

        $confederacoes = $user
            ? $user->ligas()
                ->with(['confederacao:id,nome'])
                ->get(['ligas.id', 'ligas.confederacao_id'])
                ->map(fn (Liga $liga) => $liga->confederacao)
                ->filter()
                ->unique('id')
                ->sortBy('nome')
                ->values()
                ->map(fn ($confederacao) => [
                    'id' => (string) $confederacao->id,
                    'name' => (string) $confederacao->nome,
                ])
                ->all()
            : [];

        return view('legacy.index', [
            'legacyConfig' => [
                'profileSettingsUrl' => route('legacy.profile.settings'),
                'profileUpdateUrl' => route('legacy.profile.update'),
                'profileDisponibilidadesSyncUrl' => route('legacy.profile.disponibilidades.sync'),
                'logoutUrl' => route('legacy.logout'),
                'userId' => $request->user()?->id,
                'confederacoes' => $confederacoes,
                'onboardingClubeUrl' => route('legacy.onboarding_clube'),
                'marketDataUrl' => route('legacy.market.data'),
                'myClubDataUrl' => route('legacy.my_club.data'),
                'squadDataUrl' => route('legacy.squad.data'),
                'matchCenterDataUrl' => route('legacy.match_center.data'),
                'financeDataUrl' => route('legacy.finance.data'),
                'publicClubProfileDataUrl' => route('legacy.public_club_profile.data'),
                'esquemaTaticoDataUrl' => route('legacy.esquema_tatico.data'),
                'esquemaTaticoSaveUrl' => route('legacy.esquema_tatico.save'),
            ],
        ]);
    }

    public function marketData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;

        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
                'mercado' => [
                    'players' => [],
                    'closed' => false,
                    'period' => null,
                    'radar_ids' => [],
                    'propostas_recebidas_count' => 0,
                ],
            ], 404);
        }

        $periodoAtivo = LigaPeriodo::activeRangeForLiga($liga);
        $mercadoFechado = $periodoAtivo !== null;
        $scopeConfederacaoId = $liga->confederacao_id;

        $elencosQuery = LigaClubeElenco::query()->with(['elencopadrao', 'ligaClube.liga']);

        if ($scopeConfederacaoId) {
            $elencosQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $elencosQuery->where('liga_id', $liga->id);
        }

        $elencos = $elencosQuery->get()->keyBy('elencopadrao_id');

        $userClubQuery = $user->clubesLiga()->with('liga:id,nome');
        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $userClubQuery->where('liga_id', $liga->id);
        }
        $userClub = $userClubQuery->first();

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
            ->where('user_id', $user->id);

        if ($scopeConfederacaoId) {
            $favoriteQuery->where('confederacao_id', $scopeConfederacaoId);
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

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'saldo_inicial' => (int) ($liga->saldo_inicial ?? 0),
                'jogo' => $liga->jogo?->nome,
                'multa_multiplicador' => $liga->multa_multiplicador !== null ? (float) $liga->multa_multiplicador : null,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => $userClub ? [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
                'saldo' => $walletSaldo,
                'salary_per_round' => $salaryPerRound,
            ] : null,
            'mercado' => $mercadoPayload,
        ]);
    }

    public function myClubData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
                'onboarding_url' => route('legacy.onboarding_clube'),
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $userClubQuery = $user->clubesLiga()
            ->with(['escudo:id,clube_imagem,clube_nome', 'liga:id,nome']);

        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $userClubQuery->where('liga_id', $liga->id);
        }

        $userClub = $userClubQuery->first();

        if (! $userClub) {
            return response()->json([
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
                'onboarding_url' => route('legacy.onboarding_clube', [
                    'stage' => 'confederacao',
                    'confederacao_id' => $scopeConfederacaoId,
                ]),
            ]);
        }

        $walletSaldo = (int) (LigaClubeFinanceiro::query()
            ->where('liga_id', $userClub->liga_id)
            ->where('clube_id', $userClub->id)
            ->value('saldo') ?? $liga->saldo_inicial ?? 0);

        $salaryPerRound = (int) LigaClubeElenco::query()
            ->where('liga_id', $userClub->liga_id)
            ->where('liga_clube_id', $userClub->id)
            ->where('ativo', true)
            ->sum('wage_eur');

        $elencoCount = (int) LigaClubeElenco::query()
            ->where('liga_id', $userClub->liga_id)
            ->where('liga_clube_id', $userClub->id)
            ->count();

        $fans = (int) LigaClubeConquista::query()
            ->where('liga_id', $userClub->liga_id)
            ->where('liga_clube_id', $userClub->id)
            ->whereNotNull('claimed_at')
            ->join('conquistas', 'conquistas.id', 'liga_clube_conquistas.conquista_id')
            ->sum('conquistas.fans');

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
                'escudo_id' => $userClub->escudo_clube_id,
                'escudo_nome' => $userClub->escudo?->clube_nome,
                'escudo_imagem' => $userClub->escudo?->clube_imagem,
                'liga_id' => $userClub->liga_id,
                'liga_nome' => $userClub->liga?->nome,
                'fans' => $fans,
                'saldo' => $walletSaldo,
                'salary_per_round' => $salaryPerRound,
                'elenco_count' => $elencoCount,
            ],
            'onboarding_url' => route('legacy.onboarding_clube', [
                'stage' => 'confederacao',
                'confederacao_id' => $scopeConfederacaoId,
            ]),
        ]);
    }

    public function squadData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
                'elenco' => [
                    'players' => [],
                    'player_count' => 0,
                    'active_count' => 0,
                    'salary_per_round' => 0,
                ],
                'onboarding_url' => route('legacy.onboarding_clube'),
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $userClubQuery = $user->clubesLiga()->with('liga:id,nome,max_jogadores_por_clube');

        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $userClubQuery->where('liga_id', $liga->id);
        }

        $userClub = $userClubQuery->first();

        if (! $userClub) {
            return response()->json([
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
                'elenco' => [
                    'players' => [],
                    'player_count' => 0,
                    'active_count' => 0,
                    'salary_per_round' => 0,
                ],
                'onboarding_url' => route('legacy.onboarding_clube', [
                    'stage' => 'confederacao',
                    'confederacao_id' => $scopeConfederacaoId,
                ]),
            ]);
        }

        $elencoQuery = LigaClubeElenco::query()
            ->with('elencopadrao')
            ->where('liga_clube_id', $userClub->id);

        if ($scopeConfederacaoId) {
            $elencoQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $elencoQuery->where('liga_id', $userClub->liga_id);
        }

        $elenco = $elencoQuery
            ->orderByDesc('ativo')
            ->orderByDesc('id')
            ->get();

        $salaryPerRound = (int) $elenco->sum('wage_eur');
        $activeCount = (int) $elenco->where('ativo', true)->count();

        $entries = $elenco->map(function (LigaClubeElenco $entry) {
            $player = $entry->elencopadrao;

            return [
                'id' => $entry->id,
                'ativo' => (bool) $entry->ativo,
                'value_eur' => (int) ($entry->value_eur ?? 0),
                'wage_eur' => (int) ($entry->wage_eur ?? 0),
                'elencopadrao' => [
                    'id' => $player?->id,
                    'short_name' => $player?->short_name,
                    'long_name' => $player?->long_name,
                    'player_positions' => $player?->player_positions,
                    'overall' => $player?->overall,
                    'age' => $player?->age,
                    'player_face_url' => $player?->player_face_url,
                    'value_eur' => $player?->value_eur,
                    'wage_eur' => $player?->wage_eur,
                    'weak_foot' => $player?->weak_foot,
                    'skill_moves' => $player?->skill_moves,
                    'player_traits' => $player?->player_traits,
                    'pace' => $player?->pace,
                    'shooting' => $player?->shooting,
                    'passing' => $player?->passing,
                    'dribbling' => $player?->dribbling,
                    'defending' => $player?->defending,
                    'physic' => $player?->physic,
                    'movement_acceleration' => $player?->movement_acceleration,
                    'movement_sprint_speed' => $player?->movement_sprint_speed,
                    'attacking_finishing' => $player?->attacking_finishing,
                    'power_shot_power' => $player?->power_shot_power,
                    'power_long_shots' => $player?->power_long_shots,
                    'attacking_short_passing' => $player?->attacking_short_passing,
                    'skill_long_passing' => $player?->skill_long_passing,
                    'mentality_vision' => $player?->mentality_vision,
                    'skill_dribbling' => $player?->skill_dribbling,
                    'skill_ball_control' => $player?->skill_ball_control,
                    'movement_agility' => $player?->movement_agility,
                    'movement_balance' => $player?->movement_balance,
                    'movement_reactions' => $player?->movement_reactions,
                    'defending_marking_awareness' => $player?->defending_marking_awareness,
                    'mentality_interceptions' => $player?->mentality_interceptions,
                    'defending_standing_tackle' => $player?->defending_standing_tackle,
                    'defending_sliding_tackle' => $player?->defending_sliding_tackle,
                    'power_strength' => $player?->power_strength,
                    'power_stamina' => $player?->power_stamina,
                    'power_jumping' => $player?->power_jumping,
                    'mentality_aggression' => $player?->mentality_aggression,
                ],
            ];
        })->values()->all();

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
                'max_jogadores_por_clube' => (int) ($userClub->liga?->max_jogadores_por_clube ?? 23),
            ],
            'clube' => [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
                'liga_id' => $userClub->liga_id,
                'liga_nome' => $userClub->liga?->nome,
            ],
            'elenco' => [
                'players' => $entries,
                'player_count' => count($entries),
                'active_count' => $activeCount,
                'salary_per_round' => $salaryPerRound,
            ],
            'onboarding_url' => route('legacy.onboarding_clube', [
                'stage' => 'confederacao',
                'confederacao_id' => $scopeConfederacaoId,
            ]),
        ]);
    }

    public function matchCenterData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
                'partidas' => [],
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $userClubQuery = $user->clubesLiga()->with('escudo:id,clube_imagem');

        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $userClubQuery->where('liga_id', $liga->id);
        }

        $clube = $userClubQuery->first();

        if (! $clube) {
            return response()->json([
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'timezone' => $liga->timezone,
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
                'partidas' => [],
            ]);
        }

        $partidasCollection = Partida::query()
            ->with(['mandante.user', 'visitante.user', 'mandante.escudo', 'visitante.escudo'])
            ->where('liga_id', $liga->id)
            ->where(function ($query) use ($clube): void {
                $query->where('mandante_id', $clube->id)
                    ->orWhere('visitante_id', $clube->id);
            })
            ->orderByRaw('scheduled_at IS NULL, scheduled_at ASC, created_at DESC')
            ->get();

        $avaliacoes = PartidaAvaliacao::query()
            ->whereIn('partida_id', $partidasCollection->pluck('id'))
            ->where('avaliador_user_id', $clube->user_id)
            ->get()
            ->keyBy('partida_id');

        $tz = $liga->timezone ?? 'UTC';
        $partidas = $partidasCollection
            ->map(function (Partida $partida) use ($clube, $avaliacoes, $tz) {
                $avaliacao = $avaliacoes->get($partida->id);

                return [
                    'id' => $partida->id,
                    'mandante' => $partida->mandante?->nome,
                    'visitante' => $partida->visitante?->nome,
                    'mandante_id' => $partida->mandante_id,
                    'visitante_id' => $partida->visitante_id,
                    'mandante_user_id' => $partida->mandante?->user_id,
                    'visitante_user_id' => $partida->visitante?->user_id,
                    'mandante_logo' => $this->resolveEscudoUrl($partida->mandante?->escudo?->clube_imagem),
                    'visitante_logo' => $this->resolveEscudoUrl($partida->visitante?->escudo?->clube_imagem),
                    'estado' => $partida->estado,
                    'scheduled_at' => $partida->scheduled_at ? $partida->scheduled_at->timezone($tz)->toIso8601String() : null,
                    'forced_by_system' => (bool) $partida->forced_by_system,
                    'sem_slot_disponivel' => (bool) $partida->sem_slot_disponivel,
                    'placar_mandante' => $partida->placar_mandante,
                    'placar_visitante' => $partida->placar_visitante,
                    'placar_registrado_por' => $partida->placar_registrado_por,
                    'placar_registrado_em' => $partida->placar_registrado_em?->toIso8601String(),
                    'is_mandante' => (int) $partida->mandante_id === (int) $clube->id,
                    'is_visitante' => (int) $partida->visitante_id === (int) $clube->id,
                    'avaliacao' => $avaliacao ? [
                        'nota' => $avaliacao->nota,
                        'avaliado_user_id' => $avaliacao->avaliado_user_id,
                    ] : null,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'timezone' => $tz,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => [
                'id' => $clube->id,
                'user_id' => $clube->user_id,
                'nome' => $clube->nome,
                'escudo_url' => $this->resolveEscudoUrl($clube->escudo?->clube_imagem),
            ],
            'partidas' => $partidas,
        ]);
    }

    public function financeData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
                'financeiro' => [
                    'saldo' => 0,
                    'salarioPorRodada' => 0,
                    'rodadasRestantes' => null,
                    'movimentos' => [],
                    'patrocinios' => [],
                    'ganhosPartidas' => [
                        'total' => 0,
                        'details' => [],
                    ],
                ],
                'onboarding_url' => route('legacy.onboarding_clube'),
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $userClubQuery = $user->clubesLiga()->where('liga_id', $liga->id);
        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        }

        $userClub = $userClubQuery->first();

        if (! $userClub) {
            return response()->json([
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
                'financeiro' => [
                    'saldo' => 0,
                    'salarioPorRodada' => 0,
                    'rodadasRestantes' => null,
                    'movimentos' => [],
                    'patrocinios' => [],
                    'ganhosPartidas' => [
                        'total' => 0,
                        'details' => [],
                    ],
                ],
                'onboarding_url' => route('legacy.onboarding_clube', [
                    'stage' => 'confederacao',
                    'confederacao_id' => $scopeConfederacaoId,
                ]),
            ]);
        }

        $walletSaldo = LigaClubeFinanceiro::query()
            ->where('liga_id', $liga->id)
            ->where('clube_id', $userClub->id)
            ->value('saldo');

        $saldo = $walletSaldo !== null ? (int) $walletSaldo : (int) ($liga->saldo_inicial ?? 0);

        $salaryQuery = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $userClub->id)
            ->where('ativo', true);

        if ($scopeConfederacaoId) {
            $salaryQuery->where('confederacao_id', $scopeConfederacaoId);
        }

        $salarioPorRodada = (int) $salaryQuery->sum('wage_eur');
        $rodadasRestantes = $salarioPorRodada > 0
            ? (int) floor($saldo / $salarioPorRodada)
            : null;

        $matches = Partida::query()
            ->with(['mandante:id,nome', 'visitante:id,nome'])
            ->where('liga_id', $liga->id)
            ->whereIn('estado', ['placar_registrado', 'placar_confirmado', 'wo'])
            ->where(function ($query) use ($userClub): void {
                $query->where('mandante_id', $userClub->id)
                    ->orWhere('visitante_id', $userClub->id);
            })
            ->orderByDesc('id')
            ->get([
                'id',
                'mandante_id',
                'visitante_id',
                'placar_mandante',
                'placar_visitante',
                'estado',
                'scheduled_at',
            ]);

        $ganhosPartidasTotal = 0;
        $ganhosPartidasDetalhes = [];
        foreach ($matches as $partida) {
            $earnings = $this->calculateMatchEarningsForClub($partida, (int) $userClub->id);

            if ($earnings <= 0) {
                continue;
            }

            $ganhosPartidasTotal += $earnings;
            $ganhosPartidasDetalhes[] = [
                'id' => "partida-ganho-{$partida->id}",
                'partida_id' => $partida->id,
                'label' => $this->describeMatchEarningsLabel($partida, (int) $userClub->id),
                'valor' => $earnings,
                'scheduled_at' => $partida->scheduled_at?->toDateString(),
            ];
        }

        $ganhosPartidasDetalhes = array_slice($ganhosPartidasDetalhes, 0, 5);

        $movimentosTransferenciasQuery = LigaTransferencia::query()
            ->where(function ($query) use ($userClub): void {
                $query->where('clube_origem_id', $userClub->id)
                    ->orWhere('clube_destino_id', $userClub->id);
            })
            ->with(['elencopadrao:id,short_name,long_name'])
            ->latest()
            ->limit(10);

        if ($scopeConfederacaoId) {
            $movimentosTransferenciasQuery->where('confederacao_id', $scopeConfederacaoId);
        } else {
            $movimentosTransferenciasQuery->where(function ($query) use ($liga): void {
                $query->where('liga_origem_id', $liga->id)
                    ->orWhere('liga_destino_id', $liga->id);
            });
        }

        $movimentosTransferencias = $movimentosTransferenciasQuery
            ->get([
                'id',
                'tipo',
                'valor',
                'observacao',
                'created_at',
                'clube_origem_id',
                'clube_destino_id',
                'elencopadrao_id',
            ])
            ->map(function (LigaTransferencia $movimento) {
                $player = $movimento->elencopadrao;
                $jogadorNome = $player?->short_name ?: $player?->long_name;

                return [
                    'id' => $movimento->id,
                    'tipo' => $movimento->tipo,
                    'valor' => (int) $movimento->valor,
                    'observacao' => $movimento->observacao,
                    'created_at' => $movimento->created_at,
                    'clube_origem_id' => $movimento->clube_origem_id,
                    'clube_destino_id' => $movimento->clube_destino_id,
                    'elencopadrao_id' => $movimento->elencopadrao_id,
                    'jogador_nome' => $jogadorNome,
                ];
            })
            ->values()
            ->all();

        $movimentosPartida = PartidaFolhaPagamento::query()
            ->where('liga_id', $liga->id)
            ->where('clube_id', $userClub->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'partida_id', 'total_wage', 'multa_wo', 'created_at'])
            ->flatMap(function (PartidaFolhaPagamento $pagamento) use ($userClub) {
                $items = [];

                $items[] = [
                    'id' => 'partida-salario-'.$pagamento->id,
                    'tipo' => 'salario_partida',
                    'valor' => (int) $pagamento->total_wage,
                    'observacao' => "Salário da partida #{$pagamento->partida_id}",
                    'created_at' => $pagamento->created_at,
                    'clube_origem_id' => $userClub->id,
                    'clube_destino_id' => null,
                ];

                if ((int) $pagamento->multa_wo > 0) {
                    $items[] = [
                        'id' => 'partida-multa-'.$pagamento->id,
                        'tipo' => 'multa_wo',
                        'valor' => (int) $pagamento->multa_wo,
                        'observacao' => "Multa W.O. partida #{$pagamento->partida_id}",
                        'created_at' => $pagamento->created_at,
                        'clube_origem_id' => $userClub->id,
                        'clube_destino_id' => null,
                    ];
                }

                return $items;
            })
            ->values()
            ->all();

        $movimentos = collect(array_merge($movimentosTransferencias, $movimentosPartida))
            ->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->all();

        $patrocinioResgatados = LigaClubePatrocinio::query()
            ->with('patrocinio')
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $userClub->id)
            ->whereNotNull('claimed_at')
            ->orderByDesc('claimed_at')
            ->get()
            ->map(function (LigaClubePatrocinio $registro) {
                $patrocinio = $registro->patrocinio;

                return [
                    'id' => 'patrocinio-'.$registro->id,
                    'tipo' => 'patrocinio',
                    'patrocinio_id' => $registro->patrocinio_id,
                    'valor' => (int) ($patrocinio->valor ?? 0),
                    'observacao' => $patrocinio ? "Patrocínio {$patrocinio->nome}" : 'Patrocínio resgatado',
                    'created_at' => $registro->claimed_at,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
            ],
            'financeiro' => [
                'saldo' => $saldo,
                'salarioPorRodada' => $salarioPorRodada,
                'rodadasRestantes' => $rodadasRestantes,
                'movimentos' => $movimentos,
                'patrocinios' => $patrocinioResgatados,
                'ganhosPartidas' => [
                    'total' => $ganhosPartidasTotal,
                    'details' => $ganhosPartidasDetalhes,
                ],
            ],
            'onboarding_url' => route('legacy.onboarding_clube', [
                'stage' => 'confederacao',
                'confederacao_id' => $scopeConfederacaoId,
            ]),
        ]);
    }

    public function publicClubProfileData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $rawClubId = $request->query('club_id');
        $clubId = is_numeric($rawClubId) ? (int) $rawClubId : null;
        $clubName = trim((string) $request->query('club_name', ''));

        $clubQuery = LigaClube::query()
            ->with(['escudo:id,clube_imagem,clube_nome', 'liga:id,nome'])
            ->when(
                $scopeConfederacaoId,
                fn ($query) => $query->where('confederacao_id', $scopeConfederacaoId),
                fn ($query) => $query->where('liga_id', $liga->id),
            );

        if ($clubId) {
            $clubQuery->where('id', $clubId);
        } elseif ($clubName !== '') {
            $normalized = mb_strtolower($clubName);
            $clubQuery->whereRaw('LOWER(nome) = ?', [$normalized]);
        } else {
            $clubQuery->where('user_id', $user->id);
        }

        $club = $clubQuery->first();

        if (! $club) {
            return response()->json([
                'message' => 'Clube não encontrado para esta confederação.',
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
            ], 404);
        }

        $statesWithScore = ['finalizada', 'placar_confirmado', 'wo'];
        $clubMatches = Partida::query()
            ->select(['id', 'mandante_id', 'visitante_id', 'placar_mandante', 'placar_visitante'])
            ->where('liga_id', $club->liga_id)
            ->whereIn('estado', $statesWithScore)
            ->whereNotNull('placar_mandante')
            ->whereNotNull('placar_visitante')
            ->where(function ($query) use ($club): void {
                $query->where('mandante_id', $club->id)
                    ->orWhere('visitante_id', $club->id);
            })
            ->get();

        $wins = 0;
        $goals = 0;
        foreach ($clubMatches as $match) {
            $isMandante = (int) $match->mandante_id === (int) $club->id;
            $goalsFor = (int) ($isMandante ? $match->placar_mandante : $match->placar_visitante);
            $goalsAgainst = (int) ($isMandante ? $match->placar_visitante : $match->placar_mandante);
            $goals += $goalsFor;
            if ($goalsFor > $goalsAgainst) {
                $wins++;
            }
        }

        $assists = (int) (PartidaDesempenho::query()
            ->where('liga_clube_id', $club->id)
            ->sum('assistencias') ?? 0);

        $fans = (int) LigaClubeConquista::query()
            ->where('liga_id', $club->liga_id)
            ->where('liga_clube_id', $club->id)
            ->whereNotNull('claimed_at')
            ->join('conquistas', 'conquistas.id', 'liga_clube_conquistas.conquista_id')
            ->sum('conquistas.fans');

        $trophies = LigaClubeConquista::query()
            ->where('liga_id', $club->liga_id)
            ->where('liga_clube_id', $club->id)
            ->whereNotNull('claimed_at')
            ->join('conquistas', 'conquistas.id', 'liga_clube_conquistas.conquista_id')
            ->orderByDesc('liga_clube_conquistas.claimed_at')
            ->limit(12)
            ->get([
                'conquistas.id',
                'conquistas.nome',
                'liga_clube_conquistas.claimed_at',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'nome' => (string) $row->nome,
                'claimed_at' => $row->claimed_at,
            ])
            ->values()
            ->all();

        $avaliacoes = PartidaAvaliacao::query()
            ->where('avaliado_user_id', $club->user_id)
            ->whereIn('partida_id', $clubMatches->pluck('id'))
            ->avg('nota');

        $playedMatches = max((int) $clubMatches->count(), 1);
        $winRate = $wins / $playedMatches;
        $skillRating = (int) max(50, min(99, round(60 + ($winRate * 40))));
        $uberScore = $avaliacoes !== null
            ? max(0, min(5, round((float) $avaliacoes, 1)))
            : max(1, min(5, round(3 + ($winRate * 2), 1)));

        $elencoEntries = LigaClubeElenco::query()
            ->with('elencopadrao')
            ->where('liga_clube_id', $club->id)
            ->when(
                $scopeConfederacaoId,
                fn ($query) => $query->where('confederacao_id', $scopeConfederacaoId),
                fn ($query) => $query->where('liga_id', $club->liga_id),
            )
            ->orderByDesc('ativo')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $players = $elencoEntries->map(function (LigaClubeElenco $entry) {
            $player = $entry->elencopadrao;

            return [
                'id' => (int) $entry->id,
                'nome' => (string) ($player?->short_name ?? $player?->long_name ?? 'ATLETA'),
                'pos' => (string) (explode(',', (string) ($player?->player_positions ?? ''))[0] ?? '-'),
                'ovr' => (int) ($player?->overall ?? 0),
                'valor' => (int) ($entry->value_eur ?? $player?->value_eur ?? 0),
                'salario' => (int) ($entry->wage_eur ?? $player?->wage_eur ?? 0),
                'foto' => $player?->player_face_url,
                'ativo' => (bool) $entry->ativo,
            ];
        })->values()->all();

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => [
                'id' => $club->id,
                'nome' => $club->nome,
                'escudo_url' => $this->resolveEscudoUrl($club->escudo?->clube_imagem),
                'liga_id' => $club->liga_id,
                'liga_nome' => $club->liga?->nome,
                'fans' => $fans,
                'wins' => $wins,
                'goals' => $goals,
                'assists' => $assists,
                'uber_score' => $uberScore,
                'skill_rating' => $skillRating,
                'won_trophies' => $trophies,
                'players' => $players,
            ],
        ]);
    }

    public function esquemaTaticoData(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
                'liga' => null,
                'clube' => null,
                'esquema' => [
                    'players' => [],
                    'layout' => null,
                    'image_url' => null,
                ],
                'onboarding_url' => route('legacy.onboarding_clube'),
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $userClubQuery = $user->clubesLiga()->where('liga_id', $liga->id);

        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        }

        $userClub = $userClubQuery->first();

        if (! $userClub) {
            return response()->json([
                'liga' => [
                    'id' => $liga->id,
                    'nome' => $liga->nome,
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
                'esquema' => [
                    'players' => [],
                    'layout' => null,
                    'image_url' => null,
                ],
                'onboarding_url' => route('legacy.onboarding_clube', [
                    'stage' => 'confederacao',
                    'confederacao_id' => $scopeConfederacaoId,
                ]),
            ]);
        }

        $elencoQuery = LigaClubeElenco::query()
            ->with('elencopadrao')
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $userClub->id)
            ->where('ativo', true);

        if ($scopeConfederacaoId) {
            $elencoQuery->where('confederacao_id', $scopeConfederacaoId);
        }

        $entries = $elencoQuery
            ->get()
            ->map(function (LigaClubeElenco $entry) {
                $player = $entry->elencopadrao;

                return [
                    'id' => $entry->id,
                    'short_name' => $player?->short_name,
                    'long_name' => $player?->long_name,
                    'player_positions' => $player?->player_positions,
                    'overall' => $player?->overall,
                    'player_face_url' => $player?->player_face_url,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => [
                'id' => $userClub->id,
                'nome' => $userClub->nome,
            ],
            'esquema' => [
                'players' => $entries,
                'layout' => $userClub->esquema_tatico_layout,
                'image_url' => $this->resolveEscudoUrl($userClub->esquema_tatico_imagem),
            ],
            'onboarding_url' => route('legacy.onboarding_clube', [
                'stage' => 'confederacao',
                'confederacao_id' => $scopeConfederacaoId,
            ]),
        ]);
    }

    public function salvarEsquemaTatico(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $rawConfederacaoId = $request->query('confederacao_id');
        $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;
        $liga = $this->resolveMarketLiga($user, $confederacaoId);

        if (! $liga) {
            return response()->json([
                'message' => 'Nenhuma liga encontrada para esta confederação.',
            ], 404);
        }

        $scopeConfederacaoId = $liga->confederacao_id;
        $userClubQuery = $user->clubesLiga()->where('liga_id', $liga->id);
        if ($scopeConfederacaoId) {
            $userClubQuery->where('confederacao_id', $scopeConfederacaoId);
        }

        $userClub = $userClubQuery->first();

        if (! $userClub) {
            return response()->json([
                'message' => 'Clube não encontrado para esta confederação.',
            ], 404);
        }

        $validated = $request->validate([
            'layout' => ['required', 'string'],
            'imagem' => ['required', 'image', 'max:4096'],
        ]);

        $layout = json_decode($validated['layout'], true);
        if (! is_array($layout)) {
            return response()->json([
                'message' => 'Layout inválido.',
            ], 422);
        }

        $rawPlayers = $layout['players'] ?? [];
        if (! is_array($rawPlayers)) {
            return response()->json([
                'message' => 'Layout inválido.',
            ], 422);
        }

        $validIdsQuery = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $userClub->id)
            ->pluck('id');

        if ($scopeConfederacaoId) {
            $validIdsQuery = LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $userClub->id)
                ->where('confederacao_id', $scopeConfederacaoId)
                ->pluck('id');
        }

        $validIdsMap = array_flip(
            $validIdsQuery
                ->map(fn ($id) => (int) $id)
                ->all(),
        );

        $normalizedPlayers = [];
        foreach ($rawPlayers as $player) {
            if (! is_array($player)) {
                continue;
            }

            $id = (int) ($player['id'] ?? 0);
            if (! $id || ! isset($validIdsMap[$id])) {
                continue;
            }

            $x = $player['x'] ?? null;
            $y = $player['y'] ?? null;
            if (! is_numeric($x) || ! is_numeric($y)) {
                continue;
            }

            $normalizedPlayers[] = [
                'id' => $id,
                'x' => round(max(0, min(1, (float) $x)), 4),
                'y' => round(max(0, min(1, (float) $y)), 4),
            ];
        }

        if (empty($normalizedPlayers)) {
            return response()->json([
                'message' => 'Nenhum jogador válido foi encontrado no esquema.',
            ], 422);
        }

        $file = $request->file('imagem');
        $directory = 'esquemas/'.$liga->id.'/'.$userClub->id;
        $filename = 'esquema-'.now()->format('YmdHis').'-'.Str::random(6).'.png';
        $path = $file?->storeAs($directory, $filename, 'public');

        if (! $path) {
            return response()->json([
                'message' => 'Não foi possível salvar a imagem do esquema.',
            ], 500);
        }

        if ($userClub->esquema_tatico_imagem) {
            Storage::disk('public')->delete($userClub->esquema_tatico_imagem);
        }

        $userClub->update([
            'esquema_tatico_layout' => [
                'players' => $normalizedPlayers,
            ],
            'esquema_tatico_imagem' => $path,
        ]);

        return response()->json([
            'message' => 'Esquema tático salvo com sucesso.',
            'image_url' => $this->resolveEscudoUrl($path),
        ]);
    }

    private function resolveMarketLiga(User $user, ?int $confederacaoId): ?Liga
    {
        $query = $user->ligas()
            ->with(['jogo:id,nome', 'confederacao:id,nome'])
            ->orderBy('ligas.id');

        if ($confederacaoId) {
            $query->where('ligas.confederacao_id', $confederacaoId);
        }

        return $query->first([
            'ligas.id',
            'ligas.nome',
            'ligas.saldo_inicial',
            'ligas.multa_multiplicador',
            'ligas.jogo_id',
            'ligas.confederacao_id',
            'ligas.timezone',
        ]);
    }

    private function calculateMatchEarningsForClub(Partida $partida, int $clubeId): int
    {
        $mandanteGoals = (int) ($partida->placar_mandante ?? 0);
        $visitanteGoals = (int) ($partida->placar_visitante ?? 0);

        if ($mandanteGoals === $visitanteGoals) {
            return self::MATCH_DRAW_PRIZE;
        }

        $isMandante = (int) $clubeId === (int) $partida->mandante_id;
        $mandanteWins = $mandanteGoals > $visitanteGoals;
        $clubWon = ($isMandante && $mandanteWins) || (! $isMandante && ! $mandanteWins);

        return $clubWon ? self::MATCH_WINNER_PRIZE : self::MATCH_LOSER_PRIZE;
    }

    private function describeMatchEarningsLabel(Partida $partida, int $clubeId): string
    {
        $isMandante = (int) $clubeId === (int) $partida->mandante_id;
        $mandanteGoals = (int) ($partida->placar_mandante ?? 0);
        $visitanteGoals = (int) ($partida->placar_visitante ?? 0);
        $opponent = $isMandante ? $partida->visitante?->nome : $partida->mandante?->nome;
        $opponent = $opponent ?: 'adversário';

        if ($mandanteGoals === $visitanteGoals) {
            return "Empate vs {$opponent}";
        }

        $result = ($isMandante === ($mandanteGoals > $visitanteGoals)) ? 'Vitória' : 'Derrota';

        return "{$result} vs {$opponent}";
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
