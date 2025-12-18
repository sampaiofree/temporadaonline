<?php

use App\Http\Controllers\Api\LeagueTransferController;
use App\Http\Controllers\Api\PayrollController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('/ligas/{liga}/clubes/{clube}/comprar', [LeagueTransferController::class, 'buy']);
    Route::post('/ligas/{liga}/clubes/{clube}/vender', [LeagueTransferController::class, 'sell']);
    Route::post('/ligas/{liga}/clubes/{clube}/multa', [LeagueTransferController::class, 'payReleaseClause']);
    Route::post('/ligas/{liga}/clubes/{clube}/trocar', [LeagueTransferController::class, 'swap']);

    Route::post('/ligas/{liga}/rodadas/{rodada}/cobrar-salarios', [PayrollController::class, 'chargeRound']);
});

