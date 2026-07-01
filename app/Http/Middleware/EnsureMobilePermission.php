<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobilePermission
{
    /**
     * Ensure the authenticated user holds at least one of the given Spatie
     * permissions.
     *
     * Access is permission-driven rather than tied to the `is_admin` flag or the
     * Sanctum token ability: any staff role granted the permission (e.g. an
     * "Admin" role on a user whose is_admin column is false) can reach the
     * resource. One or more permission names are passed as middleware
     * parameters (comma-separated); access is granted when the user holds ANY
     * of them, e.g. EnsureMobilePermission::class.':report.sales overview' or
     * EnsureMobilePermission::class.':sales return.create,sales return.edit'.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        $allowed = $user && collect($permissions)->contains(fn (string $permission) => $user->can($permission));

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
