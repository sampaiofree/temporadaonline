<?php

use App\Http\Controllers\Api\LeagueTransferController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\UserDisponibilidadeController;
use App\Http\Controllers\Api\PartidaScheduleController;
use App\Http\Controllers\Api\PartidaAlteracaoController;
use App\Http\Controllers\Api\PartidaActionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('/ligas/{liga}/clubes/{clube}/comprar', [LeagueTransferController::class, 'buy']);
    Route::post('/ligas/{liga}/clubes/{clube}/vender', [LeagueTransferController::class, 'sell']);
    Route::post('/ligas/{liga}/clubes/{clube}/multa', [LeagueTransferController::class, 'payReleaseClause']);
    Route::post('/ligas/{liga}/clubes/{clube}/trocar', [LeagueTransferController::class, 'swap']);

    Route::post('/ligas/{liga}/rodadas/{rodada}/cobrar-salarios', [PayrollController::class, 'chargeRound']);

    // Disponibilidades do usuário autenticado
    Route::get('/me/disponibilidades', [UserDisponibilidadeController::class, 'index']);
    Route::post('/me/disponibilidades', [UserDisponibilidadeController::class, 'store']);
    Route::put('/me/disponibilidades/{id}', [UserDisponibilidadeController::class, 'update']);
    Route::delete('/me/disponibilidades/{id}', [UserDisponibilidadeController::class, 'destroy']);

    // Partidas - opções e confirmação
    Route::get('/partidas/{partida}/opcoes', [PartidaScheduleController::class, 'opcoes']);
    Route::post('/partidas/{partida}/confirmar-horario', [PartidaScheduleController::class, 'confirmar']);
    Route::post('/partidas/{partida}/alterar-horario', [PartidaAlteracaoController::class, 'alterar']);
    Route::post('/partidas/{partida}/checkin', [PartidaActionsController::class, 'checkin']);
    Route::post('/partidas/{partida}/registrar-placar', [PartidaActionsController::class, 'registrarPlacar']);
    Route::post('/partidas/{partida}/denunciar', [PartidaActionsController::class, 'denunciar']);
});
