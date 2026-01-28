<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        // Regenerate session token to prevent 419 errors
        $request->session()->regenerateToken();

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Get current tenant from middleware
        $tenant = $this->tenantService->getCurrentTenant();

        if (! $tenant) {
            return back()->withErrors(['email' => 'Invalid subdomain or tenant not found.']);
        }

        // Find user with tenant context
        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $user || ! $user->is_active) {
            Auth::guard('web')->logout();

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records or the account is inactive.',
            ]);
        }

        // Verify user belongs to the tenant
        if ($user->tenant_id !== $tenant->id) {
            Auth::guard('web')->logout();

            return back()->withErrors([
                'email' => 'You do not have access to this tenant.',
            ]);
        }

        session(['branch_id' => $user->default_branch_id]);
        session(['branch_code' => $user->branch?->code]);
        session(['branch_name' => $user->branch?->name]);
        session(['tenant_id' => $tenant->id]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
