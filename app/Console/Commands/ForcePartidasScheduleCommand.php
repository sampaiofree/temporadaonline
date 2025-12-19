<?php

namespace App\Console\Commands;

use App\Jobs\ForcePartidasScheduleJob;
use Illuminate\Console\Command;

class ForcePartidasScheduleCommand extends Command
{
    protected $signature = 'partidas:force-schedule';
    protected $description = 'Força o agendamento de partidas em confirmacao_necessaria que já passaram do prazo de confirmação.';

    public function handle(): int
    {
        dispatch(new ForcePartidasScheduleJob());
        $this->info('Job ForcePartidasScheduleJob dispatched.');

        return self::SUCCESS;
    }
}
