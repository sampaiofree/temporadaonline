<?php

use App\Http\Controllers\Legacy\Auth\AuthenticatedSessionController as LegacyAuthenticatedSessionController;
use App\Http\Controllers\Legacy\LegacyController;
use App\Http\Controllers\Legacy\LegacyProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('legacy')->name('legacy.')->group(function () {
    Route::get('/login', [LegacyAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [LegacyAuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [LegacyAuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::get('/', function (Request $request, LegacyController $controller) {
        if (! Auth::check()) {
            return redirect()->guest(route('legacy.login'));
        }

        return $controller->index();
    })->name('index');

    Route::middleware('auth')->group(function () {
        Route::get('/profile/settings', [LegacyProfileController::class, 'show'])->name('profile.settings');
        Route::put('/profile', [LegacyProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/disponibilidades', [LegacyProfileController::class, 'syncDisponibilidades'])
            ->name('profile.disponibilidades.sync');
    });
});
