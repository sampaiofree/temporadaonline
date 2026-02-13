<?php

namespace Database\Seeders;

use App\Models\Elencopadrao;
use App\Models\Jogo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ElencopadraoSeeder extends Seeder
{
    private const MIN_PLAYERS_PER_GAME = 30;

    private const POSITION_ROTATION = [
        'GK', 'CB', 'CB', 'RB', 'LB', 'CDM', 'CM', 'CM', 'CAM', 'RW', 'LW', 'ST',
    ];

    private const NATIONALITIES = [
        'Brasil', 'Argentina', 'França', 'Espanha', 'Portugal', 'Alemanha', 'Inglaterra', 'Itália',
    ];

    public function run(): void
    {
        $jogos = Jogo::query()->orderBy('id')->get();

        if ($jogos->isEmpty()) {
            $this->command?->warn('ElencopadraoSeeder: nenhum jogo encontrado.');
            return;
        }

        foreach ($jogos as $jogo) {
            $existingCount = Elencopadrao::query()
                ->where('jogo_id', $jogo->id)
                ->count();

            $missing = max(0, self::MIN_PLAYERS_PER_GAME - $existingCount);

            if ($missing === 0) {
                $this->command?->info("ElencopadraoSeeder: {$jogo->nome} já possui {$existingCount} jogadores.");
                continue;
            }

            $existingNames = Elencopadrao::query()
                ->where('jogo_id', $jogo->id)
                ->pluck('long_name')
                ->filter()
                ->map(fn ($name) => mb_strtoupper(trim((string) $name)))
                ->flip()
                ->all();

            $created = 0;
            $slot = 1;

            while ($created < $missing) {
                $longName = mb_strtoupper("LEGACY {$jogo->slug} JOGADOR {$slot}");

                if (isset($existingNames[$longName])) {
                    $slot++;
                    continue;
                }

                Elencopadrao::create($this->buildPlayerPayload($jogo->id, (string) $jogo->slug, $slot, $longName));

                $existingNames[$longName] = true;
                $created++;
                $slot++;
            }

            $this->command?->info("ElencopadraoSeeder: {$created} jogadores criados para {$jogo->nome}.");
        }
    }

    private function buildPlayerPayload(int $jogoId, string $jogoSlug, int $slot, string $longName): array
    {
        $position = self::POSITION_ROTATION[$slot % count(self::POSITION_ROTATION)];
        $positions = $this->buildPositions($position);

        $overall = 66 + (($slot * 3) % 24); // 66..89
        $potential = min(94, $overall + 4 + ($slot % 4));
        $age = 18 + ($slot % 17); // 18..34

        $dob = Carbon::create(
            now()->year - $age,
            (($slot - 1) % 12) + 1,
            (($slot - 1) % 27) + 1
        )->toDateString();

        $valueEur = max(800_000, ($overall - 50) * 1_250_000);
        $wageEur = max(5_000, (int) round($valueEur * 0.012));

        [$pace, $shooting, $passing, $dribbling, $defending, $physic] = $this->buildPrimaryStats($position, $overall);
        [$gkDiving, $gkHandling, $gkKicking, $gkPositioning, $gkReflexes, $gkSpeed] = $this->buildGoalkeeperStats($position, $overall);

        $preferredFoot = $slot % 4 === 0 ? 'Left' : 'Right';
        $weakFoot = 2 + ($slot % 4); // 2..5
        $skillMoves = $position === 'GK' ? 1 : 2 + ($slot % 4); // 1..5
        $intlReputation = max(1, min(5, intdiv($overall - 60, 8)));
        $nationality = self::NATIONALITIES[$slot % count(self::NATIONALITIES)];

        $clubName = "Legacy FC {$slot}";
        $shortName = "LEG {$slot}";
        $playerId = strtoupper("LEG-{$jogoSlug}-{$slot}");

        return [
            'jogo_id' => $jogoId,
            'player_id' => $playerId,
            'player_url' => null,
            'short_name' => $shortName,
            'long_name' => $longName,
            'player_positions' => $positions,
            'overall' => $overall,
            'potential' => $potential,
            'value_eur' => $valueEur,
            'wage_eur' => $wageEur,
            'age' => $age,
            'dob' => $dob,
            'height_cm' => 170 + (($slot * 3) % 24),
            'weight_kg' => 65 + (($slot * 2) % 20),
            'league_name' => 'Legacy Premier',
            'league_level' => 1,
            'club_name' => $clubName,
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
            'player_face_url' => "https://placehold.co/256x256/1E1E1E/FFD700?text=P{$slot}",
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
            'ST' => 'ST,CF',
            default => $primary,
        };
    }

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

    private function ratingString(int $overall, int $offset): string
    {
        return (string) $this->clampStat($overall + $offset);
    }

    private function clampStat(int $value): int
    {
        return max(1, min(99, $value));
    }
}

