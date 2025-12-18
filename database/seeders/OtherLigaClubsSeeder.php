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

class OtherLigaClubsSeeder extends Seeder
{
    public function run(): void
    {
        $liga = Liga::where('nome', 'Liga Demo MCO')->first();
        if (! $liga) {
            return;
        }

        $usedPlayerIds = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->pluck('elencopadrao_id')
            ->all();

        $players = Elencopadrao::query()
            ->where('jogo_id', $liga->jogo_id)
            ->when(count($usedPlayerIds) > 0, function ($query) use ($usedPlayerIds) {
                return $query->whereNotIn('id', $usedPlayerIds);
            })
            ->inRandomOrder()
            ->get();

        if ($players->isEmpty()) {
            return;
        }

        $financeService = app(LeagueFinanceService::class);
        $available = $players->shuffle()->values();
        $pointer = 0;

        foreach (range(1, 3) as $index) {
            if ($pointer >= $available->count()) {
                break;
            }

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

                LigaClubeElenco::updateOrCreate(
                    [
                        'liga_id' => $liga->id,
                        'elencopadrao_id' => $player->id,
                    ],
                    [
                        'liga_clube_id' => $club->id,
                        'value_eur' => $player->value_eur ?? 0,
                        'wage_eur' => $player->wage_eur ?? 0,
                        'ativo' => true,
                    ],
                );

                $entries++;
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
