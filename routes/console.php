<?php

use App\Services\AuctionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('leiloes:finalizar-expirados {--confederacao_id=}', function (AuctionService $auctionService) {
    $rawConfederacaoId = $this->option('confederacao_id');
    $confederacaoId = is_numeric($rawConfederacaoId) ? (int) $rawConfederacaoId : null;

    $processed = $auctionService->finalizeExpiredAuctions($confederacaoId);

    $scopeLabel = $confederacaoId ? "confederacao {$confederacaoId}" : 'todas as confederacoes';
    $this->info("Leiloes finalizados em {$scopeLabel}: {$processed}");
})->purpose('Finaliza leiloes expirados e aplica as transferencias');

Schedule::command('leiloes:finalizar-expirados')
    ->everyMinute()
    ->withoutOverlapping();
