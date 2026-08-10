<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impersonation is time-boxed. Once the window closes the very next request
 * drops the admin back into their own account, so a forgotten impersonation
 * cannot quietly become a permanent second login.
 */
class EndExpiredImpersonation
{
    public function __construct(private readonly ImpersonationService $impersonation) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->impersonation->isImpersonating() || ! $this->impersonation->hasExpired()) {
            return $next($request);
        }

        if (! $this->impersonation->stop()) {
            // The original account is gone; leaving the session authenticated as
            // the target would turn a lapsed impersonation into a real login.
            $this->impersonation->forget();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your impersonation session expired. Please sign in again.');
        }

        // This middleware is in the `web` group only, so a redirect is always the
        // right response shape here — including for Livewire, which follows it.
        return redirect()->route('dashboard')->with(
            'warning',
            'Impersonation expired after '.ImpersonationService::DURATION_MINUTES.' minutes. You are back in your own account.'
        );
    }
}
