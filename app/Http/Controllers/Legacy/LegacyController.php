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
use App\Models\Playstyle;
use App\Models\PlayerFavorite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                'inboxDataUrl' => route('legacy.inbox.data'),
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
                    'pagination' => null,
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

        $playersCollection = Elencopadrao::query()
            ->select([
                'id',
                'short_name',
                'long_name',
                'player_positions',
                'overall',
                'value_eur',
                'wage_eur',
                'age',
                'weak_foot',
                'skill_moves',
                'player_traits',
                'pace',
                'shooting',
                'passing',
                'dribbling',
                'defending',
                'physic',
                'movement_acceleration',
                'movement_sprint_speed',
                'attacking_finishing',
                'power_shot_power',
                'power_long_shots',
                'attacking_short_passing',
                'skill_long_passing',
                'mentality_vision',
                'skill_dribbling',
                'skill_ball_control',
                'movement_agility',
                'movement_balance',
                'movement_reactions',
                'defending_marking_awareness',
                'mentality_interceptions',
                'defending_standing_tackle',
                'defending_sliding_tackle',
                'power_strength',
                'power_stamina',
                'power_jumping',
                'mentality_aggression',
                'player_face_url',
            ])
            ->where('jogo_id', $liga->jogo_id)
            ->orderByDesc('overall')
            ->get();

        $traitNames = $playersCollection
            ->flatMap(fn (Elencopadrao $player) => $this->parseLegacyTraitTags($player->player_traits))
            ->unique(fn (string $name) => Str::lower($name))
            ->values();

        $playstylesMap = $this->buildLegacyPlaystylesMap($traitNames);

        $players = $playersCollection
            ->map(function (Elencopadrao $player) use ($elencos, $userClub, $playstylesMap) {
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
                    'age' => $player->age,
                    'weak_foot' => $player->weak_foot,
                    'skill_moves' => $player->skill_moves,
                    'player_traits' => $player->player_traits,
                    'playstyle_badges' => $this->mapLegacyPlaystyleBadges($player->player_traits, $playstylesMap),
                    'pace' => $player->pace,
                    'shooting' => $player->shooting,
                    'passing' => $player->passing,
                    'dribbling' => $player->dribbling,
                    'defending' => $player->defending,
                    'physic' => $player->physic,
                    'movement_acceleration' => $player->movement_acceleration,
                    'movement_sprint_speed' => $player->movement_sprint_speed,
                    'attacking_finishing' => $player->attacking_finishing,
                    'power_shot_power' => $player->power_shot_power,
                    'power_long_shots' => $player->power_long_shots,
                    'attacking_short_passing' => $player->attacking_short_passing,
                    'skill_long_passing' => $player->skill_long_passing,
                    'mentality_vision' => $player->mentality_vision,
                    'skill_dribbling' => $player->skill_dribbling,
                    'skill_ball_control' => $player->skill_ball_control,
                    'movement_agility' => $player->movement_agility,
                    'movement_balance' => $player->movement_balance,
                    'movement_reactions' => $player->movement_reactions,
                    'defending_marking_awareness' => $player->defending_marking_awareness,
                    'mentality_interceptions' => $player->mentality_interceptions,
                    'defending_standing_tackle' => $player->defending_standing_tackle,
                    'defending_sliding_tackle' => $player->defending_sliding_tackle,
                    'power_strength' => $player->power_strength,
                    'power_stamina' => $player->power_stamina,
                    'power_jumping' => $player->power_jumping,
                    'mentality_aggression' => $player->mentality_aggression,
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

        $filterStatus = Str::upper(trim((string) $request->query('filter_status', 'TODOS')));
        $filterPos = Str::upper(trim((string) $request->query('filter_pos', 'TODAS')));
        $filterQuality = Str::upper(trim((string) $request->query('filter_quality', 'TODAS')));
        $sortBy = Str::upper(trim((string) $request->query('sort_by', 'OVR_DESC')));
        $filterValMinRaw = trim((string) $request->query('filter_val_min', ''));
        $filterValMaxRaw = trim((string) $request->query('filter_val_max', ''));
        $filterValMin = is_numeric($filterValMinRaw) ? (int) $filterValMinRaw : null;
        $filterValMax = is_numeric($filterValMaxRaw) ? (int) $filterValMaxRaw : null;
        $subMode = Str::lower(trim((string) $request->query('sub_mode', 'list')));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $shouldPaginate = $request->boolean('paginate')
            || $request->has('page')
            || $request->has('per_page');

        $players = $this->filterLegacyMarketPlayers(
            $players,
            $favoriteIds,
            $subMode,
            $filterStatus,
            $filterPos,
            $filterQuality,
            $filterValMin,
            $filterValMax,
            $sortBy,
        );

        [$playersPayload, $pagination] = $shouldPaginate
            ? $this->paginateLegacyMarketPlayers($players, $page, $perPage)
            : [$players->values()->all(), null];

        $mercadoPayload = [
            'players' => $playersPayload,
            'pagination' => $pagination,
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

    private function filterLegacyMarketPlayers(
        Collection $players,
        array $favoriteIds,
        string $subMode,
        string $filterStatus,
        string $filterPos,
        string $filterQuality,
        ?int $filterValMin,
        ?int $filterValMax,
        string $sortBy,
    ): Collection {
        $favoriteLookup = array_flip(array_map('intval', $favoriteIds));
        $normalizedSubMode = Str::lower($subMode);

        return $players
            ->filter(function (array $player) use (
                $favoriteLookup,
                $normalizedSubMode,
                $filterStatus,
                $filterPos,
                $filterQuality,
                $filterValMin,
                $filterValMax
            ): bool {
                $playerId = (int) ($player['elencopadrao_id'] ?? 0);
                $clubStatus = (string) ($player['club_status'] ?? 'livre');

                if ($normalizedSubMode === 'watchlist' && ! isset($favoriteLookup[$playerId])) {
                    return false;
                }

                if ($filterStatus !== 'TODOS') {
                    if ($filterStatus === 'LIVRE' && $clubStatus !== 'livre') {
                        return false;
                    }

                    if ($filterStatus === 'MEU' && $clubStatus !== 'meu') {
                        return false;
                    }

                    if ($filterStatus === 'RIVAL' && $clubStatus !== 'outro') {
                        return false;
                    }

                    if ($filterStatus === 'CONTRATADO' && $clubStatus === 'livre') {
                        return false;
                    }
                }

                $primaryPosition = $this->legacyPrimaryPositionAlias((string) ($player['player_positions'] ?? ''));
                if (! $this->matchesLegacyPositionFilter($filterPos, $primaryPosition)) {
                    return false;
                }

                $overall = (int) ($player['overall'] ?? 0);
                if (! $this->matchesLegacyQualityFilter($filterQuality, $overall)) {
                    return false;
                }

                $valueEur = (int) ($player['value_eur'] ?? 0);
                $valueInMillions = (int) max(0, round($valueEur / 1_000_000));

                if ($filterValMin !== null && $valueInMillions < $filterValMin) {
                    return false;
                }

                if ($filterValMax !== null && $valueInMillions > $filterValMax) {
                    return false;
                }

                return true;
            })
            ->sort(function (array $a, array $b) use ($sortBy): int {
                $overallA = (int) ($a['overall'] ?? 0);
                $overallB = (int) ($b['overall'] ?? 0);
                $valueA = (int) max(0, round(((int) ($a['value_eur'] ?? 0)) / 1_000_000));
                $valueB = (int) max(0, round(((int) ($b['value_eur'] ?? 0)) / 1_000_000));

                return match ($sortBy) {
                    'OVR_ASC' => ($overallA <=> $overallB) ?: ((int) ($a['elencopadrao_id'] ?? 0) <=> (int) ($b['elencopadrao_id'] ?? 0)),
                    'VAL_DESC' => ($valueB <=> $valueA) ?: ($overallB <=> $overallA),
                    'VAL_ASC' => ($valueA <=> $valueB) ?: ($overallB <=> $overallA),
                    default => ($overallB <=> $overallA) ?: ($valueB <=> $valueA),
                };
            })
            ->values();
    }

    private function paginateLegacyMarketPlayers(Collection $players, int $page, int $perPage): array
    {
        $total = $players->count();
        $lastPage = max(1, (int) ceil($total / max($perPage, 1)));
        $currentPage = min(max(1, $page), $lastPage);
        $offset = ($currentPage - 1) * $perPage;
        $items = $players->slice($offset, $perPage)->values()->all();
        $from = $total > 0 ? $offset + 1 : null;
        $to = $total > 0 ? $offset + count($items) : null;

        return [
            $items,
            [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'total' => $total,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    private function legacyPrimaryPositionAlias(string $positions): string
    {
        $positionMap = [
            'GK' => 'GOL',
            'RB' => 'LD',
            'RWB' => 'LD',
            'LB' => 'LE',
            'LWB' => 'LE',
            'CB' => 'ZAG',
            'CDM' => 'VOL',
            'CM' => 'MC',
            'CAM' => 'MEI',
            'RM' => 'MD',
            'LM' => 'ME',
            'RW' => 'PD',
            'LW' => 'PE',
            'ST' => 'ATA',
            'CF' => 'SA',
        ];

        $first = collect(explode(',', $positions))
            ->map(fn ($part) => Str::upper(trim((string) $part)))
            ->filter()
            ->first();

        if (! $first) {
            return '---';
        }

        return $positionMap[$first] ?? $first;
    }

    private function matchesLegacyPositionFilter(string $filterPos, string $position): bool
    {
        if ($filterPos === 'TODAS') {
            return true;
        }

        return match ($filterPos) {
            'ATACANTES' => in_array($position, ['ATA', 'PE', 'PD', 'SA'], true),
            'MEIO' => in_array($position, ['MC', 'MEI', 'VOL', 'ME', 'MD'], true),
            'DEFESA' => in_array($position, ['ZAG', 'LD', 'LE', 'LWB', 'RWB'], true),
            default => $position === $filterPos,
        };
    }

    private function matchesLegacyQualityFilter(string $filterQuality, int $overall): bool
    {
        return match ($filterQuality) {
            '90+' => $overall >= 90,
            '89-88' => $overall >= 88 && $overall <= 89,
            '87-84' => $overall >= 84 && $overall <= 87,
            '83-80' => $overall >= 80 && $overall <= 83,
            '79-73' => $overall >= 73 && $overall <= 79,
            '72-' => $overall <= 72,
            default => true,
        };
    }

    private function parseLegacyTraitTags(mixed $traits): Collection
    {
        if (is_array($traits)) {
            return collect($traits)
                ->map(fn ($tag) => trim((string) $tag))
                ->map(fn ($tag) => ltrim($tag, '#'))
                ->filter()
                ->values();
        }

        $raw = str_replace(['{', '}', '[', ']', '"'], '', (string) $traits);

        return collect(preg_split('/[;,|]/', $raw) ?: [])
            ->map(fn ($tag) => trim((string) $tag))
            ->map(fn ($tag) => ltrim($tag, '#'))
            ->filter()
            ->values();
    }

    private function buildLegacyPlaystylesMap(Collection $traitNames): Collection
    {
        if ($traitNames->isEmpty()) {
            return collect();
        }

        $lowerNames = $traitNames
            ->map(fn (string $name) => Str::lower($name))
            ->values();

        $placeholders = $lowerNames->map(fn () => '?')->implode(',');
        if ($placeholders === '') {
            return collect();
        }

        return Playstyle::query()
            ->whereRaw("LOWER(nome) in ({$placeholders})", $lowerNames->all())
            ->get(['nome', 'imagem'])
            ->keyBy(fn (Playstyle $playstyle) => Str::lower((string) $playstyle->nome));
    }

    private function mapLegacyPlaystyleBadges(mixed $traits, Collection $playstylesMap): array
    {
        $traitNames = $this->parseLegacyTraitTags($traits)
            ->unique(fn (string $name) => Str::lower($name))
            ->values();

        if ($traitNames->isEmpty()) {
            return [];
        }

        return $traitNames
            ->map(function (string $traitName) use ($playstylesMap) {
                $match = $playstylesMap->get(Str::lower($traitName));

                return [
                    'name' => (string) ($match?->nome ?: $traitName),
                    'image_url' => $this->resolveEscudoUrl($match?->imagem),
                ];
            })
            ->values()
            ->all();
    }

    public function inboxData(Request $request): JsonResponse
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
                'message' => 'Nenhuma liga encontrada para esta confederacao.',
                'liga' => null,
                'clube' => null,
                'inbox' => [
                    'messages' => [],
                    'summary' => $this->buildLegacyInboxSummary(0, 0, 0),
                ],
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
                    'timezone' => $liga->timezone ?? 'UTC',
                    'confederacao_id' => $liga->confederacao_id,
                    'confederacao_nome' => $liga->confederacao?->nome,
                ],
                'clube' => null,
                'inbox' => [
                    'messages' => [],
                    'summary' => $this->buildLegacyInboxSummary(0, 0, 0),
                ],
                'onboarding_url' => route('legacy.onboarding_clube', [
                    'stage' => 'confederacao',
                    'confederacao_id' => $scopeConfederacaoId,
                ]),
            ]);
        }

        $partidas = Partida::query()
            ->with(['mandante:id,nome,user_id', 'visitante:id,nome,user_id'])
            ->where('liga_id', $liga->id)
            ->whereIn('estado', [
                'confirmacao_necessaria',
                'confirmada',
                'agendada',
                'placar_registrado',
                'placar_confirmado',
                'em_reclamacao',
                'finalizada',
            ])
            ->where(function ($query) use ($clube): void {
                $query->where('mandante_id', $clube->id)
                    ->orWhere('visitante_id', $clube->id);
            })
            ->orderByRaw('scheduled_at IS NULL DESC, scheduled_at ASC, placar_registrado_em IS NULL, placar_registrado_em ASC, created_at DESC')
            ->get([
                'id',
                'mandante_id',
                'visitante_id',
                'estado',
                'scheduled_at',
                'placar_mandante',
                'placar_visitante',
                'placar_registrado_por',
                'placar_registrado_em',
                'created_at',
            ]);

        $avaliacoes = PartidaAvaliacao::query()
            ->whereIn('partida_id', $partidas->pluck('id'))
            ->where('avaliador_user_id', $user->id)
            ->get()
            ->keyBy('partida_id');

        $scheduleMatches = $partidas
            ->filter(function (Partida $partida): bool {
                return in_array((string) $partida->estado, ['confirmacao_necessaria', 'confirmada', 'agendada'], true)
                    && ! $partida->scheduled_at;
            })
            ->values();

        $confirmationMatches = $partidas
            ->filter(function (Partida $partida) use ($user): bool {
                if ((string) $partida->estado !== 'placar_registrado') {
                    return false;
                }

                return (int) ($partida->placar_registrado_por ?? 0) !== (int) $user->id;
            })
            ->values();

        $evaluationMatches = $partidas
            ->filter(function (Partida $partida) use ($avaliacoes): bool {
                if (! in_array((string) $partida->estado, ['placar_registrado', 'placar_confirmado', 'em_reclamacao', 'finalizada'], true)) {
                    return false;
                }

                return ! $avaliacoes->has($partida->id);
            })
            ->values();

        $messages = [];

        if ($scheduleMatches->isNotEmpty()) {
            $scheduleCount = (int) $scheduleMatches->count();
            $opponents = $scheduleMatches
                ->take(2)
                ->map(fn (Partida $partida) => $this->legacyOpponentName($partida, (int) $clube->id))
                ->filter()
                ->values();
            $opponentsLabel = $opponents->isNotEmpty()
                ? ' ('.$opponents->implode(', ').($scheduleCount > 2 ? ', ...' : '').')'
                : '';

            $messages[] = [
                'id' => 'schedule-pending',
                'type' => 'AGENDA',
                'title' => $scheduleCount === 1
                    ? 'PARTIDA PENDENTE DE AGENDAMENTO'
                    : 'PARTIDAS PENDENTES DE AGENDAMENTO',
                'sender' => 'MATCH CENTER',
                'content' => $scheduleCount === 1
                    ? "Voce tem 1 confronto sem horario definido{$opponentsLabel}."
                    : "Voce tem {$scheduleCount} confrontos sem horario definido{$opponentsLabel}.",
                'date' => now('UTC')->toIso8601String(),
                'urgent' => true,
                'action' => 'SCHEDULE',
                'action_label' => 'AGENDAR PARTIDAS',
            ];
        }

        foreach ($confirmationMatches as $partida) {
            $opponent = $this->legacyOpponentName($partida, (int) $clube->id);
            $mandante = $partida->placar_mandante ?? '-';
            $visitante = $partida->placar_visitante ?? '-';

            $messages[] = [
                'id' => 'confirmation-'.$partida->id,
                'type' => 'SUMULA',
                'title' => 'CONFIRMACAO DE PLACAR',
                'sender' => "VS {$opponent}",
                'content' => "Seu adversario registrou {$mandante} x {$visitante}. Confirme ou conteste no Match Center.",
                'date' => $partida->placar_registrado_em?->toIso8601String(),
                'urgent' => true,
                'action' => 'MATCH',
                'action_label' => 'IR PARA CONFRONTOS',
            ];
        }

        foreach ($evaluationMatches as $partida) {
            $opponent = $this->legacyOpponentName($partida, (int) $clube->id);

            $messages[] = [
                'id' => 'evaluation-'.$partida->id,
                'type' => 'AVALIACAO',
                'title' => 'AVALIE O ADVERSARIO',
                'sender' => "VS {$opponent}",
                'content' => 'Esta partida aguarda sua avaliacao de desempenho.',
                'date' => $partida->placar_registrado_em?->toIso8601String(),
                'urgent' => false,
                'action' => 'MATCH',
                'action_label' => 'ABRIR MATCH CENTER',
            ];
        }

        $summary = $this->buildLegacyInboxSummary(
            (int) $scheduleMatches->count(),
            (int) $confirmationMatches->count(),
            (int) $evaluationMatches->count(),
        );

        return response()->json([
            'liga' => [
                'id' => $liga->id,
                'nome' => $liga->nome,
                'timezone' => $liga->timezone ?? 'UTC',
                'confederacao_id' => $liga->confederacao_id,
                'confederacao_nome' => $liga->confederacao?->nome,
            ],
            'clube' => [
                'id' => $clube->id,
                'user_id' => $clube->user_id,
                'nome' => $clube->nome,
                'escudo_url' => $this->resolveEscudoUrl($clube->escudo?->clube_imagem),
            ],
            'inbox' => [
                'messages' => $messages,
                'summary' => $summary,
            ],
        ]);
    }

    private function legacyOpponentName(Partida $partida, int $clubId): string
    {
        $isMandante = (int) $partida->mandante_id === $clubId;
        $opponent = $isMandante ? $partida->visitante?->nome : $partida->mandante?->nome;

        return (string) ($opponent ?: 'ADVERSARIO');
    }

    private function buildLegacyInboxSummary(int $scheduleCount, int $confirmationCount, int $evaluationCount): array
    {
        $scheduleCount = max(0, $scheduleCount);
        $confirmationCount = max(0, $confirmationCount);
        $evaluationCount = max(0, $evaluationCount);
        $totalActions = $scheduleCount + $confirmationCount + $evaluationCount;
        $totalMessages = ($scheduleCount > 0 ? 1 : 0) + $confirmationCount + $evaluationCount;

        if ($totalActions === 0) {
            return [
                'has_pending_actions' => false,
                'total_actions' => 0,
                'total_messages' => 0,
                'schedule_count' => 0,
                'confirmation_count' => 0,
                'evaluation_count' => 0,
                'headline' => 'SEM ACOES PENDENTES',
                'detail' => 'Nenhuma pendencia encontrada na inbox.',
                'primary_action' => null,
            ];
        }

        $segments = [];

        if ($scheduleCount > 0) {
            $segments[] = $scheduleCount === 1
                ? '1 partida para agendar'
                : "{$scheduleCount} partidas para agendar";
        }

        if ($confirmationCount > 0) {
            $segments[] = $confirmationCount === 1
                ? '1 confirmacao de placar'
                : "{$confirmationCount} confirmacoes de placar";
        }

        if ($evaluationCount > 0) {
            $segments[] = $evaluationCount === 1
                ? '1 avaliacao pendente'
                : "{$evaluationCount} avaliacoes pendentes";
        }

        $headline = $totalActions === 1
            ? 'VOCE TEM 1 ACAO PENDENTE'
            : "VOCE TEM {$totalActions} ACOES PENDENTES";

        return [
            'has_pending_actions' => true,
            'total_actions' => $totalActions,
            'total_messages' => $totalMessages,
            'schedule_count' => $scheduleCount,
            'confirmation_count' => $confirmationCount,
            'evaluation_count' => $evaluationCount,
            'headline' => $headline,
            'detail' => implode(' e ', $segments).'.',
            'primary_action' => $scheduleCount > 0 ? 'SCHEDULE' : 'MATCH',
        ];
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
            ->where('ativo', true)
            ->when(
                $scopeConfederacaoId,
                fn ($query) => $query->where('confederacao_id', $scopeConfederacaoId),
                fn ($query) => $query->where('liga_id', $club->liga_id),
            )
            ->orderByDesc('id')
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
