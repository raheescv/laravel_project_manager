<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\TrackVisitor;
use App\Http\Middleware\TrustProxies;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

// Helper to extract 403 permission details from request context
function extract403Details(string $message, Request $request): array
{
    $user = $request->user();
    $route = $request->route();

    $action = $route?->getActionMethod();
    $controllerClass = $route?->getControllerClass();
    $resource = $controllerClass ? class_basename($controllerClass) : null;
    $resourceName = $resource ? str_replace('Controller', '', $resource) : null;

    // Try to resolve the permission name
    $permission = null;
    $isGeneric = in_array($message, ['This action is unauthorized.', ''], true);

    if (! $isGeneric && $message) {
        // abort(403, 'custom message') — use the message directly
        $permission = $message;
    } elseif ($resourceName && $action) {
        // Policy-based — reconstruct from resource + action
        // Convert PascalCase to snake_case with spaces: LocalPurchaseOrder → local purchase order
        $readable = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $resourceName));
        $permAction = $action;
        $permission = "{$readable}.{$permAction}";
    }

    return [
        'permission' => $permission,
        'action' => $action,
        'resource' => $resourceName,
        'url' => $request->path(),
        'user_role' => $user?->getRoleNames()?->implode(', '),
    ];
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/trading.php',
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/sale.php',
            __DIR__.'/../routes/purchase.php',
            __DIR__.'/../routes/accounts.php',
            __DIR__.'/../routes/inventory.php',
            __DIR__.'/../routes/issue.php',
            __DIR__.'/../routes/ticket.php',
            __DIR__.'/../routes/print.php',
            __DIR__.'/../routes/report.php',
            __DIR__.'/../routes/settings.php',
            __DIR__.'/../routes/package.php',
            __DIR__.'/../routes/flat_trade.php',
            __DIR__.'/../routes/api_log.php',
            __DIR__.'/../routes/tenant_route.php',
            __DIR__.'/../routes/tailoring.php',
            __DIR__.'/../routes/property.php',
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
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
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

        // Show 403 error page with denied permission details (AuthorizationException from policies/gates)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            return response()->view('errors.403', [
                'details' => extract403Details($e->getMessage(), $request),
            ], 403);
        });

        // Show 403 error page with details for abort(403) calls (HttpException)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 403) {
                return; // Let other HTTP exceptions pass through
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $e->getMessage() ?: 'Forbidden'], 403);
            }

            return response()->view('errors.403', [
                'details' => extract403Details($e->getMessage(), $request),
            ], 403);
        });

        // Handle authentication exceptions
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
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
