<?php

namespace Database\Seeders;

use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaClubeFinanceiro;
use App\Models\User;
use App\Services\LeagueFinanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtherLigaClubsSeeder extends Seeder
{
    private const MAX_AVAILABLE_PLAYERS = 200;

    public function run(): void
    {
        Log::info('OtherLigaClubsSeeder starting');

        try {
            $liga = Liga::where('nome', 'Liga Demo MCO')->first();
            if (! $liga) {
                Log::warning('OtherLigaClubsSeeder skipped because liga is missing');
                return;
            }
            $confederacaoId = $liga->confederacao_id;

            $usedPlayerIds = LigaClubeElenco::query()
                ->when(
                    $confederacaoId,
                    fn ($query) => $query->where('confederacao_id', $confederacaoId),
                    fn ($query) => $query->where('liga_id', $liga->id),
                )
                ->pluck('elencopadrao_id')
                ->all();

            $players = Elencopadrao::query()
                ->where('jogo_id', $liga->jogo_id)
                ->when(count($usedPlayerIds) > 0, function ($query) use ($usedPlayerIds) {
                    return $query->whereNotIn('id', $usedPlayerIds);
                })
                ->inRandomOrder()
                ->limit(self::MAX_AVAILABLE_PLAYERS)
                ->get();

            Log::info('OtherLigaClubsSeeder available players', [
                'requested' => self::MAX_AVAILABLE_PLAYERS,
                'found' => $players->count(),
            ]);

            if ($players->isEmpty()) {
                Log::warning('OtherLigaClubsSeeder no available players', ['liga_id' => $liga->id]);
                return;
            }

            $financeService = app(LeagueFinanceService::class);
            $available = $players->shuffle()->values();
            $pointer = 0;
            $clubsCreated = 0;

            foreach (range(1, 3) as $index) {
                if ($pointer >= $available->count()) {
                    break;
                }

                Log::info("OtherLigaClubsSeeder creating club {$index}", ['pointer' => $pointer]);

                $user = User::firstOrCreate(
                    ['email' => "outro-clube-{$index}@mco.gg"],
                    [
                        'name' => "Outro Clube {$index}",
                        'password' => Hash::make('password123'),
                    ],
                );

                $club = LigaClube::updateOrCreate(
                    [
                        'liga_id' => $liga->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'nome' => "Clube Parceiro {$index}",
                    ],
                );

                $financeService->initClubWallet($liga->id, $club->id);

                $entries = 0;
                while ($entries < 12 && $pointer < $available->count()) {
                    $player = $available[$pointer++];

                    $keys = $confederacaoId
                        ? [
                            'confederacao_id' => $confederacaoId,
                            'elencopadrao_id' => $player->id,
                        ]
                        : [
                            'liga_id' => $liga->id,
                            'elencopadrao_id' => $player->id,
                        ];

                    $payload = [
                        'liga_id' => $liga->id,
                        'liga_clube_id' => $club->id,
                        'value_eur' => $player->value_eur ?? 0,
                        'wage_eur' => $player->wage_eur ?? 0,
                        'ativo' => true,
                    ];

                    if ($confederacaoId) {
                        $payload['confederacao_id'] = $confederacaoId;
                    }

                    LigaClubeElenco::updateOrCreate($keys, $payload);

                    $entries++;
                }

                Log::info("OtherLigaClubsSeeder club {$club->nome} entries added", ['entries' => $entries]);

                $wallet = LigaClubeFinanceiro::updateOrCreate(
                    [
                        'liga_id' => $liga->id,
                        'clube_id' => $club->id,
                    ],
                    [
                        'saldo' => $financeService->getSaldo($liga->id, $club->id),
                    ],
                );
                $wallet->save();
                $clubsCreated++;
            }

            Log::info('OtherLigaClubsSeeder finished', [
                'liga_id' => $liga->id,
                'clubs_created' => $clubsCreated,
                'players_assigned' => $pointer,
            ]);
        } catch (\Throwable $e) {
            Log::error('OtherLigaClubsSeeder failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
