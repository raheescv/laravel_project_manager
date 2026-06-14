<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobilePermission
{
    /**
     * Ensure the authenticated user holds the given Spatie permission.
     *
     * Access is permission-driven rather than tied to the `is_admin` flag or the
     * Sanctum token ability: any staff role granted the permission (e.g. an
     * "Admin" role on a user whose is_admin column is false) can reach the
     * resource. The permission name is passed as a middleware parameter, e.g.
     * EnsureMobilePermission::class.':report.sales overview'.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
