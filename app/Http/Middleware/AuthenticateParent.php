<?php

namespace App\Http\Middleware;

use App\Models\Guardian;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The parent portal API door: a bearer token issued to an active parent, and
 * nothing else.
 *
 * Stricter than `auth:parent` on purpose. Sanctum's guard also accepts whoever is
 * signed in on the `web` session, which would let a staff member's browser
 * session through; here only a real personal access token owned by a Guardian
 * counts. A parent the school disables is locked out on their next request.
 */
class AuthenticateParent
{
    /** Where parent tokens are valid (routes/api_v1_parent.php). AppServiceProvider refuses them anywhere else. */
    public const PATH = 'api/v1/parent/*';

    public function handle(Request $request, Closure $next): Response
    {
        $guardian = Auth::guard('parent')->user();

        if (! $guardian instanceof Guardian || ! $guardian->currentAccessToken() instanceof PersonalAccessToken || ! $guardian->isActive()) {
            return response()->json(['success' => false, 'message' => 'Please sign in again.'], 401);
        }

        return $next($request);
    }
}
