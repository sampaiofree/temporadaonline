<?php

namespace Database\Seeders;

use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use App\Models\PartidaDesempenho;
use App\Models\User;
use App\Services\LeagueFinanceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LigaCincoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $liga = Liga::find(5);
        if (! $liga) {
            $this->command?->warn('Liga ID 5 não encontrada; nada foi gerado.');
            return;
        }

        $players = Elencopadrao::orderBy('id')->take(80)->pluck('id')->all();
        if (empty($players)) {
            $this->command?->warn('Nenhum jogador encontrado em elencopadrao; nada foi gerado.');
            return;
        }

        $financeService = app(LeagueFinanceService::class);
        $clubes = [];

        foreach (range(1, 4) as $index) {
            $user = User::firstOrCreate(
                ['email' => "liga5_user{$index}@example.com"],
                [
                    'name' => "Liga5 User {$index}",
                    'password' => Hash::make('password123'),
                ],
            );

            $user->profile()->updateOrCreate([], [
                'nickname' => "L5User{$index}",
            ]);

            $user->ligas()->syncWithoutDetaching([$liga->id]);

            $clube = LigaClube::firstOrCreate(
                [
                    'liga_id' => $liga->id,
                    'user_id' => $user->id,
                ],
                [
                    'nome' => "Clube Liga5 {$index}",
                ],
            );

            $financeService->initClubWallet($liga->id, $clube->id);
            $clubes[] = $clube;
        }

        if (count($clubes) < 4) {
            $this->command?->warn('Não foi possível criar os 4 clubes.');
            return;
        }

        $existingPartidas = Partida::where('liga_id', $liga->id)->count();
        if ($existingPartidas >= 6) {
            $this->command?->info('Liga 5 já possui partidas; nada criado para evitar duplicidade.');
            return;
        }

        $combos = [
            [0, 1],
            [2, 3],
            [0, 2],
            [1, 3],
            [0, 3],
            [1, 2],
        ];

        $playerPointer = 0;
        $now = Carbon::now();

        foreach ($combos as $idx => [$homeIndex, $awayIndex]) {
            $mandante = $clubes[$homeIndex];
            $visitante = $clubes[$awayIndex];

            $placarMandante = rand(0, 3);
            $placarVisitante = rand(0, 3);

            $partida = Partida::create([
                'liga_id' => $liga->id,
                'mandante_id' => $mandante->id,
                'visitante_id' => $visitante->id,
                'scheduled_at' => $now->copy()->subDays(5 - $idx)->setTime(20, 0),
                'estado' => 'finalizada',
                'placar_mandante' => $placarMandante,
                'placar_visitante' => $placarVisitante,
                'placar_registrado_por' => null,
                'placar_registrado_em' => $now->copy()->subDays(4 - $idx),
            ]);

            $homePlayerId = $players[$playerPointer % count($players)];
            $playerPointer++;
            $awayPlayerId = $players[$playerPointer % count($players)];
            $playerPointer++;

            PartidaDesempenho::create([
                'partida_id' => $partida->id,
                'liga_clube_id' => $mandante->id,
                'elencopadrao_id' => $homePlayerId,
                'nota' => rand(65, 95) / 10,
                'gols' => $placarMandante,
                'assistencias' => max(0, $placarMandante - 1),
            ]);

            PartidaDesempenho::create([
                'partida_id' => $partida->id,
                'liga_clube_id' => $visitante->id,
                'elencopadrao_id' => $awayPlayerId,
                'nota' => rand(65, 95) / 10,
                'gols' => $placarVisitante,
                'assistencias' => max(0, $placarVisitante - 1),
            ]);
        }

        $this->command?->info('Seeder LigaCincoDemoSeeder concluído.');
    }
}
