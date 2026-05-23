<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAdmin
{
    /**
     * Ensure the request is made with an admin-ability Sanctum token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin access is required for this resource.',
            ], 403);
        }

        return $next($request);
    }
}
