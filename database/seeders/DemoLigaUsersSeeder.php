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

class DemoLigaUsersSeeder extends Seeder
{
    public function run(): void
    {
        $liga = Liga::where('nome', 'Liga Demo MCO')->first();
        if (! $liga) {
            return;
        }
        $confederacaoId = $liga->confederacao_id;

        $players = Elencopadrao::where('jogo_id', $liga->jogo_id)
            ->orderBy('id')
            ->get();

        if ($players->count() < 90) {
            $players = Elencopadrao::orderBy('id')->get();
        }

        $available = $players->shuffle()->values();
        $pointer = 0;
        $financeService = app(LeagueFinanceService::class);

        foreach (range(1, 5) as $index) {
            $user = User::firstOrCreate(
                ['email' => "demo{$index}@mco.gg"],
                [
                    'name' => "Demo Jogador {$index}",
                    'password' => Hash::make('password123'),
                ],
            );

            $user->profile()->updateOrCreate([], [
                'nickname' => "Demo{$index}",
            ]);

            $user->ligas()->syncWithoutDetaching([$liga->id]);

            $club = LigaClube::updateOrCreate(
                [
                    'liga_id' => $liga->id,
                    'user_id' => $user->id,
                ],
                [
                    'nome' => "Clube Demo {$index}",
                ],
            );

            $financeService->initClubWallet($liga->id, $club->id);

            $entryCount = 0;
            while ($entryCount < 18 && $pointer < $available->count()) {
                $player = $available[$pointer++];
                $entryCount++;

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
            }

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
        }
    }
}
