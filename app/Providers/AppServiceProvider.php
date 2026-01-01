<?php

namespace App\Providers;

use App\Models\AppAsset;
use App\Models\LigaClube;
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
        LigaClube::saved(function (LigaClube $clube) {
            app(PartidaSchedulerService::class)->ensureMatchesForClub($clube, $clube->wasRecentlyCreated);
        });

        if (Schema::hasTable('app_assets')) {
            View::share('appAssets', AppAsset::query()->first());
        }
    }
}
