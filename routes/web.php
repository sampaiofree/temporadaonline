<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElencoController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\LigaClassificacaoController;
use App\Http\Controllers\LigaController;
use App\Http\Controllers\LigaDashboardController;
use App\Http\Controllers\LigaMercadoController;
use App\Http\Controllers\LigaPartidasController;
use App\Http\Controllers\LigaClubePerfilController;
use App\Http\Controllers\MinhaLigaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\UserDisponibilidadeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ligas', [LigaController::class, 'index'])->name('ligas');
    Route::post('/ligas/{liga}/entrar', [LigaController::class, 'join'])->name('ligas.join');
    Route::get('/perfil', [ProfileController::class, 'show'])->name('perfil');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('perfil.destroy');
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
    Route::get('/minha_liga', [MinhaLigaController::class, 'show'])->name('minha_liga');
    Route::get('/minha_liga/meu-elenco', [MinhaLigaController::class, 'meuElenco'])->name('minha_liga.meu_elenco');
    Route::get('/minha_liga/financeiro', [MinhaLigaController::class, 'financeiro'])->name('minha_liga.financeiro');
    Route::get('/liga/dashboard', [LigaDashboardController::class, 'show'])->name('liga.dashboard');
    Route::get('/liga/mercado', [LigaMercadoController::class, 'index'])->name('liga.mercado');
    Route::get('/liga/partidas', [LigaPartidasController::class, 'index'])->name('liga.partidas');
    Route::get('/liga/classificacao', [LigaClassificacaoController::class, 'index'])->name('liga.classificacao');
    Route::get('/liga/clubes/{clube}', [LigaClubePerfilController::class, 'show'])->name('liga.clube.perfil');
    Route::post('/minha_liga/clube/elenco', [MinhaLigaController::class, 'addPlayerToClub'])->name('minha_liga.clube.elenco');
    // Rota legada: redireciona antiga lista de elenco para o mercado da liga
    Route::get('/minha_liga/elenco', function (Illuminate\Http\Request $request) {
        $ligaId = $request->query('liga_id');
        $target = $ligaId ? route('liga.mercado', ['liga_id' => $ligaId]) : route('liga.mercado');
        return redirect()->to($target);
    })->name('minha_liga.elenco.legacy');
    Route::patch('/elenco/{elenco}/valor', [ElencoController::class, 'updateValor'])->name('elenco.updateValor');
    Route::post('/elenco/{elenco}/vender-mercado', [ElencoController::class, 'venderMercado'])->name('elenco.venderMercado');
    Route::post('/elenco/{elenco}/listar-mercado', [ElencoController::class, 'listarMercado'])->name('elenco.listarMercado');
    Route::post('/minha_liga/clubes', [MinhaLigaController::class, 'storeClube'])->name('minha_liga.clubes');

    // Disponibilidades (reuso do controller da API, para tela de perfil)
    Route::get('/me/disponibilidades', [UserDisponibilidadeController::class, 'index'])->name('me.disponibilidades.index');
});

require __DIR__.'/auth.php';
