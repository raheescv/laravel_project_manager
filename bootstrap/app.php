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
            __DIR__.'/../routes/flat_trade.php',
            __DIR__.'/../routes/api_log.php',
        ],
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/api_v1.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts();
        $middleware->use([TrustProxies::class]);
        $middleware->use([TrackVisitor::class]);

        // Exclude from CSRF token validation
        $middleware->validateCsrfTokens(except: [
            'flat_trade/webhook/post_back',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
