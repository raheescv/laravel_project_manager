<?php

use App\Http\Middleware\TrackVisitor;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/trading.php',
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/sale.php',
            __DIR__.'/../routes/purchase.php',
            __DIR__.'/../routes/accounts.php',
            __DIR__.'/../routes/inventory.php',
            __DIR__.'/../routes/print.php',
            __DIR__.'/../routes/report.php',
            __DIR__.'/../routes/settings.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts();
        $middleware->use([TrustProxies::class]);
        $middleware->use([TrackVisitor::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
