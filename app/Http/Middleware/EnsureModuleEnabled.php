<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 404 for a route whose module the tenant does not run (see ModuleAccess), e.g.
 * EnsureModuleEnabled::class.':school'. Not found rather than forbidden: for that
 * tenant the feature does not exist.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! ModuleAccess::enabled($module)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'This feature is not enabled for your business.'], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
