<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaCopaFase;
use App\Models\LigaCopaGrupo;
use App\Models\LigaCopaGrupoClube;
use App\Models\LigaCopaPartida;
use App\Models\Partida;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LigaCopaService
{
    public const PHASE_GROUPS = 'grupos';
    public const PHASE_ROUND_OF_32 = 'dezesseisavos';
    public const PHASE_ROUND_OF_16 = 'oitavas';
    public const PHASE_QUARTERFINAL = 'quartas';
    public const PHASE_SEMIFINAL = 'semi';
    public const PHASE_FINAL = 'final';

    public const STATUS_PENDING = 'pendente';
    public const STATUS_ACTIVE = 'ativa';
    public const STATUS_COMPLETED = 'concluida';
    public const STATUS_NEEDS_REVIEW = 'aguardando_correcao';

    private const GROUP_SIZE = 4;
    private const GROUP_STAGE_MATCHES_PER_GROUP = 12;
    private const RESOLVED_MATCH_STATES = ['placar_confirmado', 'wo'];

    private const PHASE_LABELS = [
        self::PHASE_GROUPS => 'Fase de Grupos',
        self::PHASE_ROUND_OF_32 => '16 avos de final',
        self::PHASE_ROUND_OF_16 => 'Oitavas de Final',
        self::PHASE_QUARTERFINAL => 'Quartas de Final',
        self::PHASE_SEMIFINAL => 'Semifinal',
        self::PHASE_FINAL => 'Final',
    ];

    public function __construct(
        private readonly PartidaSchedulerService $scheduler,
    ) {
    }

    private ?bool $schemaReadyCache = null;

    public function schemaReady(): bool
    {
        if ($this->schemaReadyCache !== null) {
            return $this->schemaReadyCache;
        }

        $this->schemaReadyCache = Schema::hasTable('liga_copa_grupos')
            && Schema::hasTable('liga_copa_grupo_clubes')
            && Schema::hasTable('liga_copa_fases')
            && Schema::hasTable('liga_copa_partidas')
            && Partida::competitionSchemaReady();

        return $this->schemaReadyCache;
    }

    public function ensureSetupForLiga(Liga $liga): void
    {
        if (! $this->schemaReady()) {
            return;
        }

        DB::transaction(function () use ($liga): void {
            $lockedLiga = Liga::query()->lockForUpdate()->findOrFail($liga->id);
            $this->ensureSetupForLockedLiga($lockedLiga);
        });
    }

    public function handleClubCreated(LigaClube $clube): void
    {
        if (! $this->schemaReady()) {
            return;
        }

        DB::transaction(function () use ($clube): void {
            $lockedClube = LigaClube::query()
                ->with('liga')
                ->lockForUpdate()
                ->findOrFail($clube->id);

            $liga = Liga::query()->lockForUpdate()->findOrFail($lockedClube->liga_id);
            $this->ensureSetupForLockedLiga($liga);
            $this->ensureClubMembershipForLockedLiga($liga, $lockedClube);
        });
    }

    public function reconcileLigaClubs(Liga $liga): int
    {
        if (! $this->schemaReady()) {
            return 0;
        }

        return DB::transaction(function () use ($liga): int {
            $lockedLiga = Liga::query()->lockForUpdate()->findOrFail($liga->id);
            $this->ensureSetupForLockedLiga($lockedLiga);

            return $this->reconcileLockedLiga($lockedLiga);
        });
    }

    public function handlePartidaResolved(Partida $partida): void
    {
        if (! $this->schemaReady()) {
            return;
        }

        if (! $partida->isCupCompetition()) {
            return;
        }

        DB::transaction(function () use ($partida): void {
            $lockedPartida = Partida::query()
                ->with(['cupMeta.fase', 'cupMeta.grupo'])
                ->lockForUpdate()
                ->findOrFail($partida->id);

            if (! $lockedPartida->isCupCompetition() || ! $lockedPartida->cupMeta) {
                return;
            }

            $liga = Liga::query()->lockForUpdate()->findOrFail($lockedPartida->liga_id);
            $this->ensureSetupForLockedLiga($liga);
            $this->syncGroupStageProgress($liga);
            $this->syncKnockoutProgress($liga);
        });
    }

    public function buildPayload(Liga $liga, ?LigaClube $viewerClub = null): array
    {
        if (! $this->schemaReady()) {
            return $this->emptyPayload($liga);
        }

        if ($this->needsSetupForLiga($liga)) {
            $this->ensureSetupForLiga($liga);
        }

        $this->reconcileLigaClubs($liga);

        $groups = LigaCopaGrupo::query()
            ->with(['memberships.ligaClube.escudo'])
            ->where('liga_id', $liga->id)
            ->orderBy('ordem')
            ->get();

        $groupItems = $groups->map(function (LigaCopaGrupo $grupo) use ($viewerClub): array {
            $standings = $this->resolveGroupStandings($grupo);

            return [
                'id' => (int) $grupo->id,
                'label' => (string) $grupo->label,
                'ordem' => (int) $grupo->ordem,
                'rows' => $standings
                    ->map(fn (array $row): array => [
                        'pos' => (int) $row['pos'],
                        'club_id' => (int) $row['club_id'],
                        'club_name' => (string) $row['club_name'],
                        'club_escudo_url' => $row['club_escudo_url'],
                        'played' => (int) $row['played'],
                        'wins' => (int) $row['wins'],
                        'draws' => (int) $row['draws'],
                        'losses' => (int) $row['losses'],
                        'points' => (int) $row['points'],
                        'goals_for' => (int) $row['goals_for'],
                        'goals_against' => (int) $row['goals_against'],
                        'goal_balance' => (int) $row['goal_balance'],
                        'qualified' => (bool) ($row['pos'] <= 2),
                        'is_user' => $viewerClub ? (int) $row['club_id'] === (int) $viewerClub->id : false,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values();

        $viewerGroup = $viewerClub
            ? $groupItems->first(function (array $group) use ($viewerClub): bool {
                return collect($group['rows'])->contains(fn (array $row): bool => (int) $row['club_id'] === (int) $viewerClub->id);
            })
            : null;

        $viewerGroupRow = $viewerGroup
            ? collect($viewerGroup['rows'])->first(fn (array $row): bool => (int) $row['club_id'] === (int) $viewerClub?->id)
            : null;

        $bracket = $this->buildBracketData($liga, $viewerClub?->id);
        $matches = $viewerClub ? $this->buildViewerMatches($liga, $viewerClub) : [];
        $groupPhase = LigaCopaFase::query()
            ->where('liga_id', $liga->id)
            ->where('tipo', self::PHASE_GROUPS)
            ->first();

        return [
            'summary' => [
                'current_phase_type' => $bracket['current_phase_type'] ?? ($groupPhase?->tipo ?? self::PHASE_GROUPS),
                'current_phase_label' => $bracket['current_phase_label'] ?? $this->phaseLabel($groupPhase?->tipo ?? self::PHASE_GROUPS),
                'group_count' => $groupItems->count(),
                'club_count' => (int) LigaClube::query()->where('liga_id', $liga->id)->count(),
                'group_stage_completed' => (string) ($groupPhase?->status ?? self::STATUS_ACTIVE) === self::STATUS_COMPLETED,
                'viewer_group_label' => $viewerGroup['label'] ?? null,
                'viewer_group_position' => $viewerGroupRow['pos'] ?? null,
                'viewer_group_points' => $viewerGroupRow['points'] ?? null,
                'viewer_group_played' => $viewerGroupRow['played'] ?? null,
                'viewer_qualified' => $viewerGroupRow ? (bool) ($viewerGroupRow['pos'] <= 2) : null,
                'viewer_matches_count' => count($matches),
                'champion' => $bracket['champion'] ?? null,
            ],
            'groups' => $groupItems->all(),
            'bracket' => $bracket,
            'matches' => $matches,
        ];
    }

    public function resolvePartidaCompetitionContext(Partida $partida): array
    {
        if (! $this->schemaReady()) {
            return $this->leagueCompetitionContext();
        }

        $partida->loadMissing(['cupMeta.fase', 'cupMeta.grupo']);

        if (! $partida->isCupCompetition()) {
            return $this->leagueCompetitionContext();
        }

        return [
            'competition_type' => Partida::COMPETITION_CUP,
            'competition_label' => 'Copa da Liga',
            'cup_phase_label' => $this->phaseLabel($partida->cupMeta?->fase?->tipo),
            'cup_group_label' => $partida->cupMeta?->grupo?->label,
        ];
    }

    public function phaseLabel(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return self::PHASE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    private function ensureSetupForLockedLiga(Liga $liga): void
    {
        $groupCount = $this->expectedGroupCount($liga);

        for ($ordem = 1; $ordem <= $groupCount; $ordem++) {
            LigaCopaGrupo::query()->firstOrCreate(
                [
                    'liga_id' => $liga->id,
                    'ordem' => $ordem,
                ],
                [
                    'label' => $this->makeGroupLabel($ordem),
                ],
            );
        }

        $this->findOrCreatePhase(
            $liga,
            self::PHASE_GROUPS,
            $this->resolvePhaseOrder($liga, self::PHASE_GROUPS),
            self::STATUS_ACTIVE,
        );
    }

    private function reconcileLockedLiga(Liga $liga): int
    {
        $clubes = LigaClube::query()
            ->where('liga_id', $liga->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $addedCount = 0;

        foreach ($clubes as $clube) {
            if ($this->ensureClubMembershipForLockedLiga($liga, $clube)) {
                $addedCount++;
            }
        }

        return $addedCount;
    }

    private function ensureClubMembershipForLockedLiga(Liga $liga, LigaClube $clube): bool
    {
        $existingMembership = LigaCopaGrupoClube::query()
            ->where('liga_clube_id', $clube->id)
            ->lockForUpdate()
            ->first();

        if ($existingMembership) {
            return false;
        }

        $lastConflict = null;

        for ($attempt = 1; $attempt <= $this->membershipAllocationAttempts($liga); $attempt++) {
            $groupSlot = $this->resolveFirstAvailableGroupSlot($liga, $clube);

            if (! $groupSlot) {
                return false;
            }

            try {
                DB::transaction(function () use ($groupSlot, $clube): void {
                    $this->createGroupMembership($groupSlot['grupo'], $clube, $groupSlot['ordem']);
                });

                $this->ensureGroupStageMatchesIfGroupIsFull($liga, $groupSlot['grupo']);

                return true;
            } catch (UniqueConstraintViolationException $exception) {
                if (! $this->isRetryableMembershipConflict($exception)) {
                    throw $exception;
                }

                $lastConflict = $exception;
                $persistedMembership = LigaCopaGrupoClube::query()
                    ->where('liga_clube_id', $clube->id)
                    ->lockForUpdate()
                    ->first();

                if ($persistedMembership) {
                    $persistedGroup = LigaCopaGrupo::query()
                        ->lockForUpdate()
                        ->findOrFail($persistedMembership->grupo_id);

                    $this->ensureGroupStageMatchesIfGroupIsFull($liga, $persistedGroup);

                    return false;
                }

                Log::warning('Liga Copa membership allocation conflict detected; retrying slot resolution.', [
                    'liga_id' => $liga->id,
                    'grupo_id' => $groupSlot['grupo']->id,
                    'liga_clube_id' => $clube->id,
                    'ordem' => $groupSlot['ordem'],
                    'attempt' => $attempt,
                ]);
            }
        }

        throw $lastConflict ?? new \RuntimeException('Liga Copa failed to allocate membership after retries.');
    }

    /**
     * @return array{grupo:LigaCopaGrupo, ordem:int}|null
     */
    protected function resolveFirstAvailableGroupSlot(Liga $liga, LigaClube $clube): ?array
    {
        $groups = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->orderBy('ordem')
            ->lockForUpdate()
            ->get();

        foreach ($groups as $grupo) {
            $usedOrdens = LigaCopaGrupoClube::query()
                ->where('grupo_id', $grupo->id)
                ->lockForUpdate()
                ->pluck('ordem')
                ->map(fn ($ordem): int => (int) $ordem)
                ->sort()
                ->values()
                ->all();

            $ordem = $this->resolveFirstAvailableGroupOrder($usedOrdens);

            if ($ordem === null) {
                continue;
            }

            if ($this->hasGroupOrderGap($usedOrdens, $ordem)) {
                Log::warning('Liga Copa found non-sequential group membership slots; reusing first available order.', [
                    'liga_id' => $liga->id,
                    'grupo_id' => $grupo->id,
                    'liga_clube_id' => $clube->id,
                    'used_ordens' => $usedOrdens,
                    'resolved_ordem' => $ordem,
                ]);
            }

            return [
                'grupo' => $grupo,
                'ordem' => $ordem,
            ];
        }

        return null;
    }

    /**
     * @param list<int> $usedOrdens
     */
    private function resolveFirstAvailableGroupOrder(array $usedOrdens): ?int
    {
        for ($ordem = 1; $ordem <= self::GROUP_SIZE; $ordem++) {
            if (! in_array($ordem, $usedOrdens, true)) {
                return $ordem;
            }
        }

        return null;
    }

    /**
     * @param list<int> $usedOrdens
     */
    private function hasGroupOrderGap(array $usedOrdens, int $resolvedOrdem): bool
    {
        return $usedOrdens !== [] && $resolvedOrdem <= count($usedOrdens);
    }

    protected function createGroupMembership(LigaCopaGrupo $grupo, LigaClube $clube, int $ordem): LigaCopaGrupoClube
    {
        return LigaCopaGrupoClube::query()->create([
            'grupo_id' => $grupo->id,
            'liga_clube_id' => $clube->id,
            'ordem' => $ordem,
        ]);
    }

    private function ensureGroupStageMatchesIfGroupIsFull(Liga $liga, LigaCopaGrupo $grupo): void
    {
        $groupMemberCount = (int) LigaCopaGrupoClube::query()
            ->where('grupo_id', $grupo->id)
            ->lockForUpdate()
            ->get(['id'])
            ->count();

        if ($groupMemberCount === self::GROUP_SIZE) {
            $this->ensureGroupStageMatches($liga, $grupo);
        }
    }

    private function membershipAllocationAttempts(Liga $liga): int
    {
        return max(3, (int) ceil((int) $liga->max_times / self::GROUP_SIZE));
    }

    private function isRetryableMembershipConflict(UniqueConstraintViolationException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'liga_copa_grupo_clubes_grupo_ordem_unique')
            || str_contains($message, 'liga_copa_grupo_clubes_grupo_id_liga_clube_id_unique')
            || str_contains($message, 'liga_copa_grupo_clubes_liga_clube_id_unique')
            || (str_contains($message, 'liga_copa_grupo_clubes') && str_contains($message, 'unique'));
    }

    private function ensureGroupStageMatches(Liga $liga, LigaCopaGrupo $grupo): void
    {
        $fase = $this->findOrCreatePhase(
            $liga,
            self::PHASE_GROUPS,
            $this->resolvePhaseOrder($liga, self::PHASE_GROUPS),
            self::STATUS_ACTIVE,
        );

        $memberships = LigaCopaGrupoClube::query()
            ->with('ligaClube')
            ->where('grupo_id', $grupo->id)
            ->orderBy('ordem')
            ->lockForUpdate()
            ->get();

        if ($memberships->count() !== self::GROUP_SIZE) {
            return;
        }

        foreach ($memberships as $mandanteMembership) {
            foreach ($memberships as $visitanteMembership) {
                if ((int) $mandanteMembership->liga_clube_id === (int) $visitanteMembership->liga_clube_id) {
                    continue;
                }

                $mandante = $mandanteMembership->ligaClube;
                $visitante = $visitanteMembership->ligaClube;

                if (! $mandante || ! $visitante) {
                    continue;
                }

                $partida = $this->scheduler->createAndSchedulePartida(
                    $liga,
                    $mandante,
                    $visitante,
                    false,
                    Partida::COMPETITION_CUP,
                );

                LigaCopaPartida::query()->updateOrCreate(
                    ['partida_id' => $partida->id],
                    [
                        'fase_id' => $fase->id,
                        'grupo_id' => $grupo->id,
                        'key_slot' => sprintf('group-%d-%d-%d', $grupo->id, $mandante->id, $visitante->id),
                        'perna' => 1,
                    ],
                );
            }
        }
    }

    private function syncGroupStageProgress(Liga $liga): void
    {
        $phase = $this->findOrCreatePhase(
            $liga,
            self::PHASE_GROUPS,
            $this->resolvePhaseOrder($liga, self::PHASE_GROUPS),
            self::STATUS_ACTIVE,
        );

        if (! $this->areAllGroupsFilled($liga)) {
            $this->setPhaseStatus($phase, self::STATUS_ACTIVE);
            return;
        }

        $phaseMatches = LigaCopaPartida::query()
            ->with('partida:id,estado,placar_mandante,placar_visitante')
            ->where('fase_id', $phase->id)
            ->whereNotNull('grupo_id')
            ->get();

        $expectedMatches = $this->expectedGroupCount($liga) * self::GROUP_STAGE_MATCHES_PER_GROUP;

        if ($phaseMatches->count() < $expectedMatches) {
            $this->setPhaseStatus($phase, self::STATUS_ACTIVE);
            return;
        }

        $resolvedMatches = $phaseMatches->filter(
            fn (LigaCopaPartida $item): bool => $item->partida !== null && $this->isResolvedCupPartida($item->partida),
        );

        if ($resolvedMatches->count() < $expectedMatches) {
            $this->setPhaseStatus($phase, self::STATUS_ACTIVE);
            return;
        }

        $this->setPhaseStatus($phase, self::STATUS_COMPLETED);

        $firstKnockoutType = $this->knockoutPhaseTypes($liga)[0] ?? null;

        if (! $firstKnockoutType) {
            return;
        }

        $existingPhase = LigaCopaFase::query()
            ->where('liga_id', $liga->id)
            ->where('tipo', $firstKnockoutType)
            ->first();

        if ($existingPhase) {
            return;
        }

        $ties = $this->buildInitialKnockoutTies($liga, $firstKnockoutType);
        if ($ties === []) {
            return;
        }

        $this->ensureKnockoutPhaseMatches(
            $liga,
            $firstKnockoutType,
            $this->resolvePhaseOrder($liga, $firstKnockoutType),
            $ties,
        );
    }

    private function syncKnockoutProgress(Liga $liga): void
    {
        $knockoutTypes = $this->knockoutPhaseTypes($liga);

        foreach ($knockoutTypes as $index => $phaseType) {
            $phase = LigaCopaFase::query()
                ->with([
                    'cupMatches.partida.mandante.escudo',
                    'cupMatches.partida.visitante.escudo',
                ])
                ->where('liga_id', $liga->id)
                ->where('tipo', $phaseType)
                ->first();

            if (! $phase) {
                break;
            }

            $analysis = $this->analyzeKnockoutPhase($phase);
            $this->setPhaseStatus($phase, $analysis['status']);

            if (! $analysis['complete']) {
                break;
            }

            if ($index === count($knockoutTypes) - 1) {
                continue;
            }

            $nextType = $knockoutTypes[$index + 1];
            $existingNextPhase = LigaCopaFase::query()
                ->where('liga_id', $liga->id)
                ->where('tipo', $nextType)
                ->first();

            if ($existingNextPhase) {
                continue;
            }

            $ties = $this->buildSequentialKnockoutTies($analysis['winners'], $nextType);

            if ($ties === []) {
                break;
            }

            $this->ensureKnockoutPhaseMatches(
                $liga,
                $nextType,
                $this->resolvePhaseOrder($liga, $nextType),
                $ties,
            );
        }
    }

    private function buildInitialKnockoutTies(Liga $liga, string $phaseType): array
    {
        $groups = LigaCopaGrupo::query()
            ->with(['memberships.ligaClube.escudo'])
            ->where('liga_id', $liga->id)
            ->orderBy('ordem')
            ->get()
            ->values();

        if ($groups->count() < 2) {
            return [];
        }

        $ties = [];
        $slotOrder = 1;

        foreach ($groups->chunk(2) as $chunk) {
            if ($chunk->count() < 2) {
                continue;
            }

            $leftGroup = $chunk->values()->get(0);
            $rightGroup = $chunk->values()->get(1);

            $leftStandings = $this->resolveGroupStandings($leftGroup);
            $rightStandings = $this->resolveGroupStandings($rightGroup);

            if ($leftStandings->count() < 2 || $rightStandings->count() < 2) {
                return [];
            }

            $leftWinner = $leftStandings->get(0)['club_model'] ?? null;
            $leftRunnerUp = $leftStandings->get(1)['club_model'] ?? null;
            $rightWinner = $rightStandings->get(0)['club_model'] ?? null;
            $rightRunnerUp = $rightStandings->get(1)['club_model'] ?? null;

            if (! $leftWinner || ! $leftRunnerUp || ! $rightWinner || ! $rightRunnerUp) {
                return [];
            }

            $ties[] = [
                'slot' => sprintf('%s-%d', $phaseType, $slotOrder++),
                'home' => $leftWinner,
                'away' => $rightRunnerUp,
            ];

            $ties[] = [
                'slot' => sprintf('%s-%d', $phaseType, $slotOrder++),
                'home' => $rightWinner,
                'away' => $leftRunnerUp,
            ];
        }

        return $ties;
    }

    private function buildSequentialKnockoutTies(Collection $winners, string $phaseType): array
    {
        if ($winners->count() < 2) {
            return [];
        }

        $ties = [];
        $slotOrder = 1;

        foreach ($winners->values()->chunk(2) as $chunk) {
            if ($chunk->count() < 2) {
                continue;
            }

            $home = $chunk->get(0)['club'] ?? null;
            $away = $chunk->get(1)['club'] ?? null;

            if (! $home || ! $away) {
                return [];
            }

            $ties[] = [
                'slot' => sprintf('%s-%d', $phaseType, $slotOrder++),
                'home' => $home,
                'away' => $away,
            ];
        }

        return $ties;
    }

    private function ensureKnockoutPhaseMatches(Liga $liga, string $phaseType, int $order, array $ties): void
    {
        $phase = $this->findOrCreatePhase($liga, $phaseType, $order, self::STATUS_ACTIVE);

        foreach ($ties as $tie) {
            /** @var LigaClube|null $home */
            $home = $tie['home'] ?? null;
            /** @var LigaClube|null $away */
            $away = $tie['away'] ?? null;
            $slot = (string) ($tie['slot'] ?? '');

            if (! $home || ! $away || $slot === '') {
                continue;
            }

            $legOne = $this->scheduler->createAndSchedulePartida(
                $liga,
                $home,
                $away,
                false,
                Partida::COMPETITION_CUP,
            );

            $legTwo = $this->scheduler->createAndSchedulePartida(
                $liga,
                $away,
                $home,
                false,
                Partida::COMPETITION_CUP,
            );

            LigaCopaPartida::query()->updateOrCreate(
                ['partida_id' => $legOne->id],
                [
                    'fase_id' => $phase->id,
                    'grupo_id' => null,
                    'key_slot' => $slot,
                    'perna' => 1,
                ],
            );

            LigaCopaPartida::query()->updateOrCreate(
                ['partida_id' => $legTwo->id],
                [
                    'fase_id' => $phase->id,
                    'grupo_id' => null,
                    'key_slot' => $slot,
                    'perna' => 2,
                ],
            );
        }

        $this->setPhaseStatus($phase, self::STATUS_ACTIVE);
    }

    private function analyzeKnockoutPhase(LigaCopaFase $phase): array
    {
        $slotGroups = $phase->cupMatches
            ->sortBy(fn (LigaCopaPartida $item): int => $this->extractSlotOrder((string) $item->key_slot))
            ->groupBy(fn (LigaCopaPartida $item): string => (string) $item->key_slot);

        if ($slotGroups->isEmpty()) {
            return [
                'status' => self::STATUS_PENDING,
                'complete' => false,
                'winners' => collect(),
            ];
        }

        $winners = collect();
        $hasIncompleteSlot = false;
        $needsReview = false;

        foreach ($slotGroups as $slot => $slotMatches) {
            $resolution = $this->resolveKnockoutSlotWinner($slotMatches);

            if ((bool) ($resolution['needs_review'] ?? false)) {
                $needsReview = true;
                continue;
            }

            if (! (bool) ($resolution['resolved'] ?? false)) {
                $hasIncompleteSlot = true;
                continue;
            }

            $winnerClubId = (int) ($resolution['winner_club_id'] ?? 0);
            $winnerClub = $this->resolveClubFromKnockoutEntries($slotMatches, $winnerClubId);

            if (! $winnerClub) {
                $hasIncompleteSlot = true;
                continue;
            }

            $winners->push([
                'slot' => (string) $slot,
                'slot_order' => $this->extractSlotOrder((string) $slot),
                'club' => $winnerClub,
            ]);
        }

        if ($needsReview) {
            return [
                'status' => self::STATUS_NEEDS_REVIEW,
                'complete' => false,
                'winners' => collect(),
            ];
        }

        if ($hasIncompleteSlot || $winners->count() !== $slotGroups->count()) {
            return [
                'status' => self::STATUS_ACTIVE,
                'complete' => false,
                'winners' => collect(),
            ];
        }

        return [
            'status' => self::STATUS_COMPLETED,
            'complete' => true,
            'winners' => $winners->sortBy('slot_order')->values(),
        ];
    }

    private function resolveKnockoutSlotWinner(Collection $slotMatches): array
    {
        $legs = $slotMatches
            ->sortBy(fn (LigaCopaPartida $item): int => (int) ($item->perna ?? 0))
            ->values();

        $legOne = $legs->first(fn (LigaCopaPartida $item): bool => (int) ($item->perna ?? 0) === 1);
        $legTwo = $legs->first(fn (LigaCopaPartida $item): bool => (int) ($item->perna ?? 0) === 2);

        if (! $legOne || ! $legTwo || ! $legOne->partida || ! $legTwo->partida) {
            return ['resolved' => false, 'winner_club_id' => null, 'needs_review' => false];
        }

        $matchOne = $legOne->partida;
        $matchTwo = $legTwo->partida;

        if (! $this->isResolvedCupPartida($matchOne) || ! $this->isResolvedCupPartida($matchTwo)) {
            return ['resolved' => false, 'winner_club_id' => null, 'needs_review' => false];
        }

        $clubAId = (int) $matchOne->mandante_id;
        $clubBId = (int) $matchOne->visitante_id;

        if ($clubAId <= 0 || $clubBId <= 0) {
            return ['resolved' => false, 'winner_club_id' => null, 'needs_review' => false];
        }

        $aggregateA = $this->scoreForClub($matchOne, $clubAId) + $this->scoreForClub($matchTwo, $clubAId);
        $aggregateB = $this->scoreForClub($matchOne, $clubBId) + $this->scoreForClub($matchTwo, $clubBId);

        if ($aggregateA === $aggregateB) {
            return ['resolved' => false, 'winner_club_id' => null, 'needs_review' => true];
        }

        return [
            'resolved' => true,
            'winner_club_id' => $aggregateA > $aggregateB ? $clubAId : $clubBId,
            'needs_review' => false,
        ];
    }

    private function resolveGroupStandings(LigaCopaGrupo $grupo): Collection
    {
        $grupo->loadMissing(['memberships.ligaClube.escudo']);

        $memberships = $grupo->memberships
            ->sortBy('ordem')
            ->values();

        $matches = Partida::query()
            ->with(['mandante.escudo', 'visitante.escudo'])
            ->whereHas('cupMeta', fn ($query) => $query->where('grupo_id', $grupo->id))
            ->whereIn('estado', self::RESOLVED_MATCH_STATES)
            ->whereNotNull('placar_mandante')
            ->whereNotNull('placar_visitante')
            ->get([
                'id',
                'mandante_id',
                'visitante_id',
                'placar_mandante',
                'placar_visitante',
                'estado',
                'competition_type',
            ]);

        $stats = [];
        foreach ($memberships as $membership) {
            $club = $membership->ligaClube;
            if (! $club) {
                continue;
            }

            $stats[(int) $club->id] = [
                'club_model' => $club,
                'club_id' => (int) $club->id,
                'club_name' => (string) $club->nome,
                'club_escudo_url' => $this->resolveEscudoUrl($club->escudo?->clube_imagem),
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_balance' => 0,
                'seed_order' => (int) $membership->ordem,
            ];
        }

        foreach ($matches as $match) {
            $mandanteId = (int) $match->mandante_id;
            $visitanteId = (int) $match->visitante_id;

            if (! isset($stats[$mandanteId], $stats[$visitanteId])) {
                continue;
            }

            $mandanteGoals = (int) ($match->placar_mandante ?? 0);
            $visitanteGoals = (int) ($match->placar_visitante ?? 0);

            $stats[$mandanteId]['played']++;
            $stats[$visitanteId]['played']++;
            $stats[$mandanteId]['goals_for'] += $mandanteGoals;
            $stats[$mandanteId]['goals_against'] += $visitanteGoals;
            $stats[$visitanteId]['goals_for'] += $visitanteGoals;
            $stats[$visitanteId]['goals_against'] += $mandanteGoals;

            if ($mandanteGoals > $visitanteGoals) {
                $stats[$mandanteId]['wins']++;
                $stats[$mandanteId]['points'] += 3;
                $stats[$visitanteId]['losses']++;
            } elseif ($mandanteGoals < $visitanteGoals) {
                $stats[$visitanteId]['wins']++;
                $stats[$visitanteId]['points'] += 3;
                $stats[$mandanteId]['losses']++;
            } else {
                $stats[$mandanteId]['draws']++;
                $stats[$visitanteId]['draws']++;
                $stats[$mandanteId]['points']++;
                $stats[$visitanteId]['points']++;
            }
        }

        $rows = array_values(array_map(function (array $row): array {
            $row['goal_balance'] = (int) $row['goals_for'] - (int) $row['goals_against'];

            return $row;
        }, $stats));

        $tieGroupSizes = collect($rows)
            ->groupBy(fn (array $row): string => $this->groupTieKey($row))
            ->map(fn (Collection $group): int => $group->count());

        usort($rows, function (array $left, array $right) use ($matches, $tieGroupSizes): int {
            $baseComparison = $this->compareGroupRowsBase($left, $right);

            if ($baseComparison !== 0) {
                return $baseComparison;
            }

            $tieKey = $this->groupTieKey($left);
            $tieGroupSize = (int) ($tieGroupSizes[$tieKey] ?? 0);

            if ($tieGroupSize === 2) {
                $headToHeadComparison = $this->compareHeadToHead($left, $right, $matches);

                if ($headToHeadComparison !== 0) {
                    return $headToHeadComparison;
                }
            }

            return (int) $left['seed_order'] <=> (int) $right['seed_order'];
        });

        return collect($rows)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['pos'] = $index + 1;

                return $row;
            });
    }

    private function compareGroupRowsBase(array $left, array $right): int
    {
        if ((int) $left['points'] !== (int) $right['points']) {
            return (int) $right['points'] <=> (int) $left['points'];
        }

        if ((int) $left['goal_balance'] !== (int) $right['goal_balance']) {
            return (int) $right['goal_balance'] <=> (int) $left['goal_balance'];
        }

        if ((int) $left['goals_for'] !== (int) $right['goals_for']) {
            return (int) $right['goals_for'] <=> (int) $left['goals_for'];
        }

        return 0;
    }

    private function compareHeadToHead(array $left, array $right, Collection $matches): int
    {
        $clubAId = (int) $left['club_id'];
        $clubBId = (int) $right['club_id'];

        $headToHeadMatches = $matches
            ->filter(function (Partida $match) use ($clubAId, $clubBId): bool {
                $mandanteId = (int) $match->mandante_id;
                $visitanteId = (int) $match->visitante_id;

                return ($mandanteId === $clubAId && $visitanteId === $clubBId)
                    || ($mandanteId === $clubBId && $visitanteId === $clubAId);
            })
            ->values();

        $stats = [
            $clubAId => ['points' => 0, 'goal_balance' => 0, 'goals_for' => 0],
            $clubBId => ['points' => 0, 'goal_balance' => 0, 'goals_for' => 0],
        ];

        foreach ($headToHeadMatches as $match) {
            $mandanteId = (int) $match->mandante_id;
            $visitanteId = (int) $match->visitante_id;
            $mandanteGoals = (int) ($match->placar_mandante ?? 0);
            $visitanteGoals = (int) ($match->placar_visitante ?? 0);

            $stats[$mandanteId]['goals_for'] += $mandanteGoals;
            $stats[$visitanteId]['goals_for'] += $visitanteGoals;
            $stats[$mandanteId]['goal_balance'] += $mandanteGoals - $visitanteGoals;
            $stats[$visitanteId]['goal_balance'] += $visitanteGoals - $mandanteGoals;

            if ($mandanteGoals > $visitanteGoals) {
                $stats[$mandanteId]['points'] += 3;
            } elseif ($mandanteGoals < $visitanteGoals) {
                $stats[$visitanteId]['points'] += 3;
            } else {
                $stats[$mandanteId]['points']++;
                $stats[$visitanteId]['points']++;
            }
        }

        if ($stats[$clubAId]['points'] !== $stats[$clubBId]['points']) {
            return $stats[$clubBId]['points'] <=> $stats[$clubAId]['points'];
        }

        if ($stats[$clubAId]['goal_balance'] !== $stats[$clubBId]['goal_balance']) {
            return $stats[$clubBId]['goal_balance'] <=> $stats[$clubAId]['goal_balance'];
        }

        if ($stats[$clubAId]['goals_for'] !== $stats[$clubBId]['goals_for']) {
            return $stats[$clubBId]['goals_for'] <=> $stats[$clubAId]['goals_for'];
        }

        return 0;
    }

    private function buildBracketData(Liga $liga, ?int $viewerClubId = null): array
    {
        $phases = LigaCopaFase::query()
            ->with([
                'cupMatches.partida.mandante.escudo',
                'cupMatches.partida.visitante.escudo',
            ])
            ->where('liga_id', $liga->id)
            ->whereIn('tipo', $this->knockoutPhaseTypes($liga))
            ->orderBy('ordem')
            ->get();

        $phaseItems = $phases->map(function (LigaCopaFase $phase) use ($viewerClubId): array {
            $slotGroups = $phase->cupMatches
                ->sortBy(fn (LigaCopaPartida $item): int => $this->extractSlotOrder((string) $item->key_slot))
                ->groupBy(fn (LigaCopaPartida $item): string => (string) $item->key_slot);

            return [
                'id' => (int) $phase->id,
                'type' => (string) $phase->tipo,
                'label' => (string) $this->phaseLabel($phase->tipo),
                'status' => (string) $phase->status,
                'matches' => $slotGroups->map(function (Collection $slotMatches, string $slot) use ($viewerClubId): array {
                    $resolution = $this->resolveKnockoutSlotWinner($slotMatches);
                    $legs = $slotMatches
                        ->sortBy(fn (LigaCopaPartida $item): int => (int) ($item->perna ?? 0))
                        ->values();

                    $legOne = $legs->first(fn (LigaCopaPartida $item): bool => (int) ($item->perna ?? 0) === 1);
                    $aggregate = null;

                    if ($legOne && $legOne->partida && (bool) ($resolution['resolved'] ?? false)) {
                        $clubAId = (int) $legOne->partida->mandante_id;
                        $clubBId = (int) $legOne->partida->visitante_id;
                        $aggregate = [
                            'home_club_id' => $clubAId,
                            'home_club_name' => $legOne->partida->mandante?->nome,
                            'away_club_id' => $clubBId,
                            'away_club_name' => $legOne->partida->visitante?->nome,
                            'home_score' => $this->aggregateScoreForClub($legs, $clubAId),
                            'away_score' => $this->aggregateScoreForClub($legs, $clubBId),
                        ];
                    }

                    return [
                        'slot' => (string) $slot,
                        'slot_order' => $this->extractSlotOrder((string) $slot),
                        'resolved' => (bool) ($resolution['resolved'] ?? false),
                        'needs_review' => (bool) ($resolution['needs_review'] ?? false),
                        'winner_club_id' => $resolution['winner_club_id'] ?? null,
                        'winner_club_name' => $this->resolveClubFromKnockoutEntries($slotMatches, (int) ($resolution['winner_club_id'] ?? 0))?->nome,
                        'is_user_involved' => $viewerClubId
                            ? $this->isViewerInKnockoutSlot($slotMatches, $viewerClubId)
                            : false,
                        'aggregate' => $aggregate,
                        'legs' => $legs->map(fn (LigaCopaPartida $item): array => [
                            'partida_id' => (int) ($item->partida_id ?? 0),
                            'perna' => (int) ($item->perna ?? 0),
                            'estado' => (string) ($item->partida?->estado ?? 'confirmacao_necessaria'),
                            'scheduled_at' => $item->partida?->scheduled_at?->toIso8601String(),
                            'mandante_id' => (int) ($item->partida?->mandante_id ?? 0),
                            'visitante_id' => (int) ($item->partida?->visitante_id ?? 0),
                            'mandante' => $item->partida?->mandante?->nome,
                            'visitante' => $item->partida?->visitante?->nome,
                            'mandante_logo' => $this->resolveEscudoUrl($item->partida?->mandante?->escudo?->clube_imagem),
                            'visitante_logo' => $this->resolveEscudoUrl($item->partida?->visitante?->escudo?->clube_imagem),
                            'placar_mandante' => $item->partida?->placar_mandante,
                            'placar_visitante' => $item->partida?->placar_visitante,
                        ])->all(),
                    ];
                })->values()->all(),
            ];
        })->values();

        $currentPhase = $phaseItems->first(
            fn (array $phase): bool => in_array((string) ($phase['status'] ?? ''), [self::STATUS_ACTIVE, self::STATUS_NEEDS_REVIEW], true),
        );
        $lastPhase = $phaseItems->last();
        $champion = null;

        if ($lastPhase && (string) ($lastPhase['type'] ?? '') === self::PHASE_FINAL) {
            $finalMatch = collect($lastPhase['matches'])->first(fn (array $match): bool => ! empty($match['winner_club_id']));

            if ($finalMatch) {
                $champion = [
                    'club_id' => (int) ($finalMatch['winner_club_id'] ?? 0),
                    'club_name' => (string) ($finalMatch['winner_club_name'] ?? 'CLUBE'),
                ];
            }
        }

        return [
            'current_phase_type' => $currentPhase['type'] ?? ($lastPhase['type'] ?? null),
            'current_phase_label' => $currentPhase['label'] ?? ($lastPhase['label'] ?? null),
            'phases' => $phaseItems->all(),
            'champion' => $champion,
        ];
    }

    private function buildViewerMatches(Liga $liga, LigaClube $viewerClub): array
    {
        return Partida::query()
            ->with(['mandante.escudo', 'visitante.escudo', 'cupMeta.fase', 'cupMeta.grupo'])
            ->cupCompetition()
            ->where('liga_id', $liga->id)
            ->where(function ($query) use ($viewerClub): void {
                $query->where('mandante_id', $viewerClub->id)
                    ->orWhere('visitante_id', $viewerClub->id);
            })
            ->orderByRaw('scheduled_at IS NULL, scheduled_at ASC, created_at DESC')
            ->get()
            ->map(function (Partida $partida) use ($viewerClub): array {
                $context = $this->resolvePartidaCompetitionContext($partida);

                return [
                    'id' => (int) $partida->id,
                    'estado' => (string) $partida->estado,
                    'scheduled_at' => $partida->scheduled_at?->toIso8601String(),
                    'mandante_id' => (int) $partida->mandante_id,
                    'visitante_id' => (int) $partida->visitante_id,
                    'mandante' => $partida->mandante?->nome,
                    'visitante' => $partida->visitante?->nome,
                    'mandante_logo' => $this->resolveEscudoUrl($partida->mandante?->escudo?->clube_imagem),
                    'visitante_logo' => $this->resolveEscudoUrl($partida->visitante?->escudo?->clube_imagem),
                    'placar_mandante' => $partida->placar_mandante,
                    'placar_visitante' => $partida->placar_visitante,
                    'is_mandante' => (int) $partida->mandante_id === (int) $viewerClub->id,
                    'is_visitante' => (int) $partida->visitante_id === (int) $viewerClub->id,
                    'competition_type' => $context['competition_type'],
                    'competition_label' => $context['competition_label'],
                    'cup_phase_label' => $context['cup_phase_label'],
                    'cup_group_label' => $context['cup_group_label'],
                ];
            })
            ->values()
            ->all();
    }

    private function findOrCreatePhase(Liga $liga, string $phaseType, int $order, string $status): LigaCopaFase
    {
        return LigaCopaFase::query()->firstOrCreate(
            [
                'liga_id' => $liga->id,
                'tipo' => $phaseType,
            ],
            [
                'ordem' => $order,
                'status' => $status,
            ],
        );
    }

    private function setPhaseStatus(LigaCopaFase $phase, string $status): void
    {
        if ((string) $phase->status === $status) {
            return;
        }

        $phase->update(['status' => $status]);
    }

    private function areAllGroupsFilled(Liga $liga): bool
    {
        $groups = LigaCopaGrupo::query()
            ->where('liga_id', $liga->id)
            ->withCount('memberships')
            ->orderBy('ordem')
            ->get();

        if ($groups->count() !== $this->expectedGroupCount($liga)) {
            return false;
        }

        return $groups->every(fn (LigaCopaGrupo $grupo): bool => (int) $grupo->memberships_count === self::GROUP_SIZE);
    }

    private function expectedGroupCount(Liga $liga): int
    {
        return max(0, intdiv(max((int) $liga->max_times, 0), self::GROUP_SIZE));
    }

    private function resolvePhaseOrder(Liga $liga, string $phaseType): int
    {
        $sequence = $this->phaseSequenceForLiga($liga);
        $index = array_search($phaseType, $sequence, true);

        return $index === false ? count($sequence) : $index + 1;
    }

    private function phaseSequenceForLiga(Liga $liga): array
    {
        return array_merge([self::PHASE_GROUPS], $this->knockoutPhaseTypes($liga));
    }

    private function knockoutPhaseTypes(Liga $liga): array
    {
        return match ((int) $liga->max_times) {
            8 => [self::PHASE_SEMIFINAL, self::PHASE_FINAL],
            16 => [self::PHASE_QUARTERFINAL, self::PHASE_SEMIFINAL, self::PHASE_FINAL],
            32 => [self::PHASE_ROUND_OF_16, self::PHASE_QUARTERFINAL, self::PHASE_SEMIFINAL, self::PHASE_FINAL],
            64 => [self::PHASE_ROUND_OF_32, self::PHASE_ROUND_OF_16, self::PHASE_QUARTERFINAL, self::PHASE_SEMIFINAL, self::PHASE_FINAL],
            default => [],
        };
    }

    private function needsSetupForLiga(Liga $liga): bool
    {
        if (! $this->schemaReady()) {
            return false;
        }

        $expectedGroups = $this->expectedGroupCount($liga);
        $groupCount = LigaCopaGrupo::query()->where('liga_id', $liga->id)->count();

        if ($groupCount < $expectedGroups) {
            return true;
        }

        return ! LigaCopaFase::query()
            ->where('liga_id', $liga->id)
            ->where('tipo', self::PHASE_GROUPS)
            ->exists();
    }

    private function makeGroupLabel(int $ordem): string
    {
        return 'Grupo '.chr(64 + max(1, min($ordem, 26)));
    }

    private function isResolvedCupPartida(Partida $partida): bool
    {
        return in_array((string) $partida->estado, self::RESOLVED_MATCH_STATES, true)
            && $partida->placar_mandante !== null
            && $partida->placar_visitante !== null;
    }

    private function scoreForClub(Partida $partida, int $clubId): int
    {
        if ((int) $partida->mandante_id === $clubId) {
            return (int) ($partida->placar_mandante ?? 0);
        }

        if ((int) $partida->visitante_id === $clubId) {
            return (int) ($partida->placar_visitante ?? 0);
        }

        return 0;
    }

    private function aggregateScoreForClub(Collection $legs, int $clubId): int
    {
        return $legs->sum(fn (LigaCopaPartida $item): int => $item->partida ? $this->scoreForClub($item->partida, $clubId) : 0);
    }

    private function resolveClubFromKnockoutEntries(Collection $slotMatches, int $clubId): ?LigaClube
    {
        foreach ($slotMatches as $item) {
            if (! $item instanceof LigaCopaPartida || ! $item->partida) {
                continue;
            }

            if ((int) $item->partida->mandante_id === $clubId) {
                return $item->partida->mandante;
            }

            if ((int) $item->partida->visitante_id === $clubId) {
                return $item->partida->visitante;
            }
        }

        return null;
    }

    private function isViewerInKnockoutSlot(Collection $slotMatches, int $viewerClubId): bool
    {
        return $slotMatches->contains(function (LigaCopaPartida $item) use ($viewerClubId): bool {
            if (! $item->partida) {
                return false;
            }

            return (int) $item->partida->mandante_id === $viewerClubId
                || (int) $item->partida->visitante_id === $viewerClubId;
        });
    }

    private function extractSlotOrder(string $keySlot): int
    {
        if (preg_match('/-(\d+)$/', $keySlot, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }

        return 0;
    }

    private function groupTieKey(array $row): string
    {
        return implode('|', [
            (int) ($row['points'] ?? 0),
            (int) ($row['goal_balance'] ?? 0),
            (int) ($row['goals_for'] ?? 0),
        ]);
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

    private function emptyPayload(Liga $liga): array
    {
        return [
            'summary' => [
                'current_phase_type' => null,
                'current_phase_label' => null,
                'group_count' => 0,
                'club_count' => (int) LigaClube::query()->where('liga_id', $liga->id)->count(),
                'group_stage_completed' => false,
                'viewer_group_label' => null,
                'viewer_group_position' => null,
                'viewer_group_points' => null,
                'viewer_group_played' => null,
                'viewer_qualified' => null,
                'viewer_matches_count' => 0,
                'champion' => null,
            ],
            'groups' => [],
            'bracket' => [
                'current_phase_type' => null,
                'current_phase_label' => null,
                'phases' => [],
                'champion' => null,
            ],
            'matches' => [],
        ];
    }

    private function leagueCompetitionContext(): array
    {
        return [
            'competition_type' => Partida::COMPETITION_LEAGUE,
            'competition_label' => 'Liga',
            'cup_phase_label' => null,
            'cup_group_label' => null,
        ];
    }
}
