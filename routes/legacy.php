<?php

use App\Http\Controllers\Legacy\Auth\AuthenticatedSessionController as LegacyAuthenticatedSessionController;
use App\Http\Controllers\Legacy\LegacyController;
use App\Http\Controllers\Legacy\LegacyOnboardingClubeController;
use App\Http\Controllers\Legacy\LegacyProfileController;
use App\Http\Controllers\Legacy\PrimeiroAcessoController;
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

        return $controller->index($request);
    })->name('index');

    Route::middleware('auth')->group(function () {
        Route::get('/primeiro-acesso', [PrimeiroAcessoController::class, 'show'])->name('primeiro_acesso');
        Route::put('/primeiro-acesso/profile', [PrimeiroAcessoController::class, 'updateProfile'])
            ->name('primeiro_acesso.profile.update');
        Route::put('/primeiro-acesso/disponibilidades', [PrimeiroAcessoController::class, 'syncDisponibilidades'])
            ->name('primeiro_acesso.disponibilidades.sync');

        Route::get('/onboarding-clube', [LegacyOnboardingClubeController::class, 'show'])
            ->name('onboarding_clube');
        Route::post('/onboarding-clube/select-liga', [LegacyOnboardingClubeController::class, 'selectLiga'])
            ->name('onboarding_clube.select_liga');
        Route::post('/onboarding-clube/clubes', [LegacyOnboardingClubeController::class, 'storeClube'])
            ->name('onboarding_clube.store');
        Route::get('/market-data', [LegacyController::class, 'marketData'])
            ->name('market.data');
        Route::get('/my-club-data', [LegacyController::class, 'myClubData'])
            ->name('my_club.data');
        Route::get('/squad-data', [LegacyController::class, 'squadData'])
            ->name('squad.data');
        Route::get('/match-center-data', [LegacyController::class, 'matchCenterData'])
            ->name('match_center.data');
        Route::get('/finance-data', [LegacyController::class, 'financeData'])
            ->name('finance.data');
        Route::get('/public-club-profile-data', [LegacyController::class, 'publicClubProfileData'])
            ->name('public_club_profile.data');
        Route::get('/esquema-tatico-data', [LegacyController::class, 'esquemaTaticoData'])
            ->name('esquema_tatico.data');
        Route::post('/esquema-tatico', [LegacyController::class, 'salvarEsquemaTatico'])
            ->name('esquema_tatico.save');

        Route::get('/profile/settings', [LegacyProfileController::class, 'show'])->name('profile.settings');
        Route::put('/profile', [LegacyProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/disponibilidades', [LegacyProfileController::class, 'syncDisponibilidades'])
            ->name('profile.disponibilidades.sync');
    });
});
