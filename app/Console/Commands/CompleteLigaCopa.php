<?php

namespace App\Console\Commands;

use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaCopaFase;
use App\Models\LigaCopaGrupo;
use App\Models\LigaCopaGrupoClube;
use App\Models\LigaCopaPartida;
use App\Models\Partida;
use App\Models\User;
use App\Services\LigaClubProvisioningService;
use App\Services\LigaCopaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompleteLigaCopa extends Command
{
    protected $signature = 'liga:copa:complete
        {liga_id : ID da liga a ser normalizada/completada}
        {--target-max-times=8 : Novo max_times permitido para a liga}';

    protected $description = 'Reconstrói o metadado da Copa de uma liga, importa clubes antigos e cria os clubes demo faltantes.';

    private const ALLOWED_MAX_TIMES = [8, 16, 32, 64];

    private const POSITION_ROTATION = [
        'GK', 'RB', 'LB', 'CB', 'CB', 'CB', 'CDM', 'CDM', 'CM', 'CM', 'CAM', 'CAM', 'ST', 'ST', 'LW', 'RW', 'LM', 'RM',
    ];

    private const NATIONALITIES = [
        'Brasil', 'Argentina', 'França', 'Espanha', 'Portugal', 'Alemanha', 'Inglaterra', 'Itália',
    ];

    public function __construct(
        private readonly LigaCopaService $ligaCopaService,
        private readonly LigaClubProvisioningService $clubProvisioningService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ligaId = (int) $this->argument('liga_id');
        $targetMaxTimes = (int) $this->option('target-max-times');

        if (! in_array($targetMaxTimes, self::ALLOWED_MAX_TIMES, true)) {
            $this->error('target-max-times precisa ser um de: 8, 16, 32, 64.');
            return self::FAILURE;
        }

        $liga = Liga::query()->with('jogo')->find($ligaId);

        if (! $liga) {
            $this->error("Liga {$ligaId} não encontrada.");
            return self::FAILURE;
        }

        try {
            $this->resetCupMetadataForLiga($liga, $targetMaxTimes);

            $liga = Liga::query()->with('jogo')->findOrFail($ligaId);

            $this->ligaCopaService->ensureSetupForLiga($liga);
            $importedExistingClubs = $this->ligaCopaService->reconcileLigaClubs($liga);

            $currentClubCount = (int) LigaClube::query()->where('liga_id', $liga->id)->count();
            $missingClubCount = max(0, $targetMaxTimes - $currentClubCount);

            if ($missingClubCount > 0) {
                $requiredAvailablePlayers = $missingClubCount * LigaClubProvisioningService::INITIAL_ROSTER_SIZE;
                $createdPlayers = $this->ensureSyntheticPlayersAvailability($liga, $requiredAvailablePlayers);

                if ($createdPlayers > 0) {
                    $this->info("{$createdPlayers} jogadores sintéticos criados para completar o pool da liga.");
                }
            }

            $startIndex = $currentClubCount + 1;
            $endIndex = $targetMaxTimes;

            for ($slot = $startIndex; $slot <= $endIndex; $slot++) {
                $user = $this->firstOrCreateDemoUser($slot);
                $result = $this->clubProvisioningService->provision($liga, $user, [
                    'nome' => sprintf('AA Clube %02d', $slot),
                    'nickname' => sprintf('AACopa%02d', $slot),
                    'whatsapp' => sprintf('1199000%04d', $slot),
                    'user_name' => sprintf('AA Copa %02d', $slot),
                ]);

                if ($result['clube']->wasRecentlyCreated && (int) $result['initialAdded'] < LigaClubProvisioningService::INITIAL_ROSTER_SIZE) {
                    throw new RuntimeException(sprintf(
                        'O clube demo %s foi criado com elenco incompleto (%d/%d).',
                        $result['clube']->nome,
                        (int) $result['initialAdded'],
                        LigaClubProvisioningService::INITIAL_ROSTER_SIZE,
                    ));
                }
            }

            $this->ligaCopaService->reconcileLigaClubs($liga);

            $liga = Liga::query()->findOrFail($liga->id);
            $groupIds = LigaCopaGrupo::query()
                ->where('liga_id', $liga->id)
                ->pluck('id');

            $finalClubCount = (int) LigaClube::query()->where('liga_id', $liga->id)->count();
            $finalCupClubCount = $groupIds->isEmpty()
                ? 0
                : (int) LigaCopaGrupoClube::query()->whereIn('grupo_id', $groupIds)->count();
            $finalCupMatches = (int) Partida::query()
                ->where('liga_id', $liga->id)
                ->cupCompetition()
                ->count();
            $expectedCupMatches = intdiv((int) $liga->max_times, 4) * 12;

            if ($finalClubCount !== (int) $liga->max_times) {
                throw new RuntimeException(sprintf(
                    'Liga finalizou com %d clubes, mas o esperado era %d.',
                    $finalClubCount,
                    (int) $liga->max_times,
                ));
            }

            if ($finalCupClubCount !== (int) $liga->max_times) {
                throw new RuntimeException(sprintf(
                    'Copa finalizou com %d clubes alocados, mas o esperado era %d.',
                    $finalCupClubCount,
                    (int) $liga->max_times,
                ));
            }

            if ($finalCupMatches !== $expectedCupMatches) {
                throw new RuntimeException(sprintf(
                    'Copa finalizou com %d partidas, mas o esperado era %d.',
                    $finalCupMatches,
                    $expectedCupMatches,
                ));
            }

            $this->line(sprintf('Liga: %s (#%d)', $liga->nome, $liga->id));
            $this->line(sprintf('max_times: %d', (int) $liga->max_times));
            $this->line(sprintf('clubes na liga: %d', $finalClubCount));
            $this->line(sprintf('clubes alocados na copa: %d', $finalCupClubCount));
            $this->line(sprintf('partidas de copa: %d', $finalCupMatches));
            $this->line(sprintf('clubes antigos importados na copa: %d', $importedExistingClubs));

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function resetCupMetadataForLiga(Liga $liga, int $targetMaxTimes): void
    {
        DB::transaction(function () use ($liga, $targetMaxTimes): void {
            $lockedLiga = Liga::query()->lockForUpdate()->findOrFail($liga->id);
            $clubCount = (int) LigaClube::query()->where('liga_id', $lockedLiga->id)->count();

            if ($clubCount > $targetMaxTimes) {
                throw new RuntimeException(sprintf(
                    'A liga já possui %d clubes, acima do novo max_times %d.',
                    $clubCount,
                    $targetMaxTimes,
                ));
            }

            $groupIds = LigaCopaGrupo::query()
                ->where('liga_id', $lockedLiga->id)
                ->pluck('id');

            $phaseIds = LigaCopaFase::query()
                ->where('liga_id', $lockedLiga->id)
                ->pluck('id');

            $hasCupMemberships = $groupIds->isNotEmpty()
                && LigaCopaGrupoClube::query()->whereIn('grupo_id', $groupIds)->exists();

            if ($hasCupMemberships) {
                throw new RuntimeException('A liga já possui clubes alocados na Copa. O command abortou para não destruir progresso existente.');
            }

            $hasCupMatches = Partida::query()
                ->where('liga_id', $lockedLiga->id)
                ->cupCompetition()
                ->exists();

            if ($hasCupMatches) {
                throw new RuntimeException('A liga já possui partidas de Copa. O command abortou para não destruir progresso existente.');
            }

            $lockedLiga->update([
                'max_times' => $targetMaxTimes,
            ]);

            if ($phaseIds->isNotEmpty()) {
                LigaCopaPartida::query()->whereIn('fase_id', $phaseIds)->delete();
            }

            if ($groupIds->isNotEmpty()) {
                LigaCopaGrupoClube::query()->whereIn('grupo_id', $groupIds)->delete();
            }

            LigaCopaFase::query()->where('liga_id', $lockedLiga->id)->delete();
            LigaCopaGrupo::query()->where('liga_id', $lockedLiga->id)->delete();
        }, 3);
    }

    private function firstOrCreateDemoUser(int $slot): User
    {
        $email = sprintf('aa-liga-copa-%02d@mco.local', $slot);
        $name = sprintf('AA Copa %02d', $slot);

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password123',
                'email_verified_at' => now(),
            ],
        );

        if (! $user->email_verified_at || (string) $user->name !== $name) {
            $user->forceFill([
                'name' => $name,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        }

        $user->password = 'password123';
        $user->save();

        return $user;
    }

    private function ensureSyntheticPlayersAvailability(Liga $liga, int $requiredAvailablePlayers): int
    {
        $availableCount = $this->clubProvisioningService->countAvailablePlayersForLiga($liga);
        $missingCount = max(0, $requiredAvailablePlayers - $availableCount);

        if ($missingCount === 0) {
            return 0;
        }

        $liga->loadMissing('jogo');

        $existingNames = Elencopadrao::query()
            ->where('jogo_id', $liga->jogo_id)
            ->pluck('long_name')
            ->filter()
            ->map(fn ($name) => mb_strtoupper(trim((string) $name)))
            ->flip()
            ->all();

        $created = 0;
        $slot = 1;

        while ($created < $missingCount) {
            $longName = mb_strtoupper(sprintf('AA LIGA COPA JOGADOR %03d', $slot));

            if (isset($existingNames[$longName])) {
                $slot++;
                continue;
            }

            Elencopadrao::query()->create(
                $this->buildSyntheticPlayerPayload(
                    jogoId: (int) $liga->jogo_id,
                    jogoSlug: (string) ($liga->jogo?->slug ?: 'aa-liga'),
                    slot: $slot,
                    longName: $longName,
                ),
            );

            $existingNames[$longName] = true;
            $created++;
            $slot++;
        }

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSyntheticPlayerPayload(int $jogoId, string $jogoSlug, int $slot, string $longName): array
    {
        $position = self::POSITION_ROTATION[($slot - 1) % count(self::POSITION_ROTATION)];
        $overall = 66 + (($slot * 3) % 24);
        $potential = min(94, $overall + 4 + ($slot % 4));
        $age = 18 + ($slot % 17);
        $valueEur = max(800_000, ($overall - 50) * 1_250_000);
        $wageEur = max(5_000, (int) round($valueEur * 0.012));
        $preferredFoot = $slot % 4 === 0 ? 'Left' : 'Right';
        $weakFoot = 2 + ($slot % 4);
        $skillMoves = $position === 'GK' ? 1 : 2 + ($slot % 4);
        $intlReputation = max(1, min(5, intdiv($overall - 60, 8)));
        $nationality = self::NATIONALITIES[$slot % count(self::NATIONALITIES)];
        $dob = Carbon::create(
            now()->year - $age,
            (($slot - 1) % 12) + 1,
            (($slot - 1) % 27) + 1,
        )->toDateString();

        [$pace, $shooting, $passing, $dribbling, $defending, $physic] = $this->buildPrimaryStats($position, $overall);
        [$gkDiving, $gkHandling, $gkKicking, $gkPositioning, $gkReflexes, $gkSpeed] = $this->buildGoalkeeperStats($position, $overall);

        return [
            'jogo_id' => $jogoId,
            'player_id' => strtoupper(sprintf('AA-LIGA-%s-%03d', $jogoSlug, $slot)),
            'player_url' => null,
            'short_name' => sprintf('AA %03d', $slot),
            'long_name' => $longName,
            'player_positions' => $this->buildPositions($position),
            'overall' => $overall,
            'potential' => $potential,
            'value_eur' => $valueEur,
            'wage_eur' => $wageEur,
            'age' => $age,
            'dob' => $dob,
            'height_cm' => 170 + (($slot * 3) % 24),
            'weight_kg' => 65 + (($slot * 2) % 20),
            'league_name' => 'AA Liga Demo',
            'league_level' => 1,
            'club_name' => sprintf('AA Player Pool %02d', $slot),
            'club_position' => $position,
            'club_jersey_number' => (($slot - 1) % 99) + 1,
            'nationality_name' => $nationality,
            'preferred_foot' => $preferredFoot,
            'weak_foot' => $weakFoot,
            'skill_moves' => $skillMoves,
            'international_reputation' => $intlReputation,
            'work_rate' => 'High/Medium',
            'body_type' => 'Normal',
            'real_face' => false,
            'release_clause_eur' => $valueEur * 2,
            'pace' => $pace,
            'shooting' => $shooting,
            'passing' => $passing,
            'dribbling' => $dribbling,
            'defending' => $defending,
            'physic' => $physic,
            'goalkeeping_diving' => $gkDiving,
            'goalkeeping_handling' => $gkHandling,
            'goalkeeping_kicking' => $gkKicking,
            'goalkeeping_positioning' => $gkPositioning,
            'goalkeeping_reflexes' => $gkReflexes,
            'goalkeeping_speed' => $gkSpeed,
            'st' => $this->ratingString($overall, $position === 'ST' ? 2 : -4),
            'cm' => $this->ratingString($overall, $position === 'CM' ? 2 : -2),
            'cb' => $this->ratingString($overall, $position === 'CB' ? 2 : -5),
            'gk' => $this->ratingString($overall, $position === 'GK' ? 3 : -20),
            'player_face_url' => sprintf('https://placehold.co/256x256/1E1E1E/FFD700?text=AA%03d', $slot),
        ];
    }

    private function buildPositions(string $primary): string
    {
        return match ($primary) {
            'GK' => 'GK',
            'CB' => 'CB,RCB,LCB',
            'RB' => 'RB,RWB',
            'LB' => 'LB,LWB',
            'CDM' => 'CDM,CM',
            'CM' => 'CM,CDM,CAM',
            'CAM' => 'CAM,CM',
            'RW' => 'RW,RM',
            'LW' => 'LW,LM',
            'LM' => 'LM,LW',
            'RM' => 'RM,RW',
            'ST' => 'ST,CF',
            default => $primary,
        };
    }

    /**
     * @return array<int, int>
     */
    private function buildPrimaryStats(string $position, int $overall): array
    {
        if ($position === 'GK') {
            return [
                $this->clampStat(45 + ($overall % 20)),
                $this->clampStat(20 + ($overall % 12)),
                $this->clampStat(50 + ($overall % 16)),
                $this->clampStat(35 + ($overall % 16)),
                $this->clampStat(18 + ($overall % 15)),
                $this->clampStat(58 + ($overall % 18)),
            ];
        }

        $bias = match ($position) {
            'ST' => [4, 8, -1, 3, -14, 2],
            'RW', 'LW' => [8, 3, 2, 7, -18, -2],
            'LM', 'RM' => [5, 0, 3, 4, -10, 0],
            'CAM' => [3, 4, 8, 5, -10, -1],
            'CM' => [1, 1, 6, 2, 2, 2],
            'CDM' => [-1, -4, 4, -2, 8, 4],
            'CB' => [-7, -8, -2, -6, 11, 7],
            'RB', 'LB' => [4, -3, 2, 1, 7, 4],
            default => [0, 0, 0, 0, 0, 0],
        };

        return [
            $this->clampStat($overall + $bias[0]),
            $this->clampStat($overall + $bias[1]),
            $this->clampStat($overall + $bias[2]),
            $this->clampStat($overall + $bias[3]),
            $this->clampStat($overall + $bias[4]),
            $this->clampStat($overall + $bias[5]),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function buildGoalkeeperStats(string $position, int $overall): array
    {
        if ($position === 'GK') {
            return [
                $this->clampStat($overall + 2),
                $this->clampStat($overall + 1),
                $this->clampStat($overall - 1),
                $this->clampStat($overall + 2),
                $this->clampStat($overall + 3),
                $this->clampStat(38 + ($overall % 20)),
            ];
        }

        return [12, 10, 15, 11, 13, 28];
    }

    private function clampStat(int $value): int
    {
        return max(10, min(99, $value));
    }

    private function ratingString(int $overall, int $offset): string
    {
        return (string) max(10, min(99, $overall + $offset));
    }
}
