<?php

namespace App\Providers;

use App\Models\AppAsset;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Services\LigaCopaService;
use App\Services\PartidaSchedulerService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Liga::created(function (Liga $liga): void {
            $ligaCopaService = app(LigaCopaService::class);
            if ($ligaCopaService->schemaReady()) {
                $ligaCopaService->ensureSetupForLiga($liga);
            }
        });

        LigaClube::created(function (LigaClube $clube): void {
            app(PartidaSchedulerService::class)->ensureMatchesForClub($clube, true);

            $ligaCopaService = app(LigaCopaService::class);
            if ($ligaCopaService->schemaReady()) {
                $ligaCopaService->handleClubCreated($clube);
            }
        });

        if (Schema::hasTable('app_assets')) {
            View::share('appAssets', AppAsset::query()->first());
        }
    }
}
