<?php

namespace Database\Seeders;

use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaPeriodo;
use App\Models\Partida;
use App\Models\PartidaAlteracao;
use App\Models\PartidaEvento;
use App\Models\User;
use App\Models\UserDisponibilidade;
use App\Services\PartidaSchedulerService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LigaPartidasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $liga = Liga::query()->find(1);
        if (! $liga) {
            Log::warning('LigaPartidasDemoSeeder: liga_id=1 não encontrada');
            return;
        }

        $liga->update([
            'timezone' => $liga->timezone ?: 'America/Sao_Paulo',
        ]);

        if ($liga->periodos()->count() === 0) {
            $start = Carbon::now($liga->timezone)->startOfDay();
            $end = $start->copy()->addDays(30);

            LigaPeriodo::create([
                'liga_id' => $liga->id,
                'inicio' => $start->toDateString(),
                'fim' => $end->toDateString(),
            ]);
        }

        $clubs = LigaClube::query()
            ->where('liga_id', $liga->id)
            ->get();

        if ($clubs->count() < 2) {
            Log::warning('LigaPartidasDemoSeeder: menos de 2 clubes na liga.');
            return;
        }

        // Limpa partidas antigas da liga (e tabelas filhas)
        $partidaIds = Partida::query()->where('liga_id', $liga->id)->pluck('id');
        PartidaEvento::query()->whereIn('partida_id', $partidaIds)->delete();
        PartidaAlteracao::query()->whereIn('partida_id', $partidaIds)->delete();
        Partida::query()->whereIn('id', $partidaIds)->delete();

        // Configura users e disponibilidades (alguns sem disponibilidade para testar sem-slot)
        $clubs->each(function (LigaClube $club, int $index): void {
            $user = $club->user;
            if (! $user) {
                $user = User::firstOrCreate(
                    ['email' => "liga1-clube{$club->id}@mco.gg"],
                    [
                        'name' => $club->nome ?? "Clube {$club->id}",
                        'password' => Hash::make('password123'),
                    ],
                );
                $club->user_id = $user->id;
                $club->save();
            }

            // a cada 3º clube, não cria disponibilidade para testar cenário sem slot
            if (($index + 1) % 3 === 0) {
                UserDisponibilidade::query()->where('user_id', $user->id)->delete();
                return;
            }

            UserDisponibilidade::query()->where('user_id', $user->id)->delete();
            foreach ([1, 2, 3, 4, 5] as $day) {
                UserDisponibilidade::create([
                    'user_id' => $user->id,
                    'dia_semana' => $day,
                    'hora_inicio' => '18:00',
                    'hora_fim' => '23:00',
                ]);
            }
        });

        $scheduler = app(PartidaSchedulerService::class);

        // Gera turno e returno para todos os pares
        $clubArray = $clubs->values();
        for ($i = 0; $i < $clubArray->count(); $i++) {
            for ($j = $i + 1; $j < $clubArray->count(); $j++) {
                $mandante = $clubArray[$i];
                $visitante = $clubArray[$j];
                $scheduler->createAndSchedulePartida($liga, $mandante, $visitante, false);
                $scheduler->createAndSchedulePartida($liga, $visitante, $mandante, false);
            }
        }

        Log::info('LigaPartidasDemoSeeder concluído', [
            'liga_id' => $liga->id,
            'clubs' => $clubs->count(),
            'partidas' => Partida::query()->where('liga_id', $liga->id)->count(),
            'timezone' => $liga->timezone,
        ]);
    }
}
