<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\TrackVisitor;
use App\Http\Middleware\TrustProxies;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

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
            __DIR__.'/../routes/package.php',
            __DIR__.'/../routes/flat_trade.php',
            __DIR__.'/../routes/api_log.php',
            __DIR__.'/../routes/tenant_route.php',
            __DIR__.'/../routes/tailoring.php',
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
        // Trust hosts
        $middleware->trustHosts();

        // Global middleware
        $middleware->use([
            TrustProxies::class,
            TrackVisitor::class,
        ]);

        // Add tenant identification early in web middleware stack
        $middleware->web(prepend: [IdentifyTenant::class]);

        // Add Inertia middleware to the web group
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Exclude routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'flat_trade/webhook/post_back',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Show 419 error page when session expires or CSRF token mismatch
        $exceptions->render(function (TokenMismatchException $e, \Illuminate\Http\Request $request) {
            // Regenerate session to get a fresh CSRF token
            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            // Handle API/JSON requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'CSRF token mismatch. Please refresh the page.'], 419);
            }

            // Handle Inertia requests
            if ($request->header('X-Inertia')) {
                return redirect()->guest(route('login'))->with('error', 'Your session has expired. Please login again.');
            }

            // Handle regular web requests - show 419 error page
            return response()->view('errors.419', [], 419);
        });

        // Handle authentication exceptions
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, \Illuminate\Http\Request $request) {
            if ($exception instanceof AuthenticationException) {
                // Handle API/JSON requests
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                // Handle Inertia requests
                if ($request->header('X-Inertia')) {
                    return redirect()->guest(route('login'))->with('error', 'Your session has expired. Please login again.');
                }

                // Handle regular web requests
                return redirect()->guest(route('login'));
            }

            return $response;
        });
    })
    ->create();
