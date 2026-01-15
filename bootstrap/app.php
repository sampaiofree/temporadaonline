<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureRosterLimitDuringMarketClosed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            api: __DIR__.'/../routes/api.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
->withMiddleware(function (Middleware $middleware): void {
            $middleware->web(append: [
                \App\Http\Middleware\HandleInertiaRequests::class,
                \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            ]);

            $middleware->alias([
                'admin' => AdminMiddleware::class,
                'roster.limit' => EnsureRosterLimitDuringMarketClosed::class,
            ]);
        })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
