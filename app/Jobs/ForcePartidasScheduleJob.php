<?php

namespace App\Jobs;

use App\Models\Liga;
use App\Models\Partida;
use App\Services\PartidaSchedulerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ForcePartidasScheduleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $scheduler = app(PartidaSchedulerService::class);

        Partida::query()
            ->where('estado', 'confirmacao_necessaria')
            ->where('sem_slot_disponivel', false)
            ->with('liga')
            ->chunkById(100, function ($partidas) use ($scheduler): void {
                foreach ($partidas as $partida) {
                    $liga = $partida->liga;
                    $prazoHoras = (int) ($liga->prazo_confirmacao_horas ?? 48);
                    $limite = Carbon::parse($partida->created_at)->addHours($prazoHoras);

                    if (Carbon::now('UTC')->lessThanOrEqualTo($limite)) {
                        continue;
                    }

                    $scheduler->forceSchedule($partida);
                }
            });
    }
}
