<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Configuration;
use App\Models\User;
use App\Services\TenantService;
use App\Support\LoginScreen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Never rotate the CSRF token here: every open tab shares one session, so
        // loading /login in one tab would 419 the login form already open in another.
        return view('auth.login', ['screen' => $this->screen(LoginScreen::resolve())]);
    }

    /**
     * The sign-in screen as a signed-in admin sees it from Settings → Login Page,
     * with the chosen layout and background forced and the form inert.
     */
    public function preview(Request $request): View
    {
        $resolved = LoginScreen::resolve();

        return view('auth.login', ['screen' => $this->screen([
            'layout' => LoginScreen::sanitize($request->query('layout'), LoginScreen::LAYOUTS) === LoginScreen::RANDOM
                ? $resolved['layout'] : $request->query('layout'),
            'background' => LoginScreen::sanitize($request->query('background'), LoginScreen::BACKGROUNDS) === LoginScreen::RANDOM
                ? $resolved['background'] : $request->query('background'),
        ], preview: true)]);
    }

    /**
     * Handle an incoming authentication request. The Vue sign-in screen posts
     * JSON and gets the redirect target back; a plain form post is redirected.
     *
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $tenant = $this->tenantService->getCurrentTenant();

        if (! $tenant) {
            $this->reject('Invalid subdomain or tenant not found.');
        }

        $user = User::withoutGlobalScopes()
            ->whereKey(Auth::id())
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $user || ! $user->is_active) {
            $this->reject('The provided credentials do not match our records or the account is inactive.');
        }

        session(['branch_id' => $user->default_branch_id]);
        session(['branch_code' => $user->branch?->code]);
        session(['branch_name' => $user->branch?->name]);
        session(['tenant_id' => $tenant->id]);

        $request->session()->regenerate();

        $redirect = redirect()->intended(route('dashboard', absolute: false));

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $redirect->getTargetUrl(),
                'user' => ['name' => $user->name],
            ]);
        }

        return $redirect;
    }

    /**
     * @param  array{layout: string, background: string}  $look
     * @return array<string, mixed>
     */
    protected function screen(array $look, bool $preview = false): array
    {
        $logo = Configuration::where('key', 'logo')->value('value');

        return [
            ...$look,
            'copy' => LoginScreen::copy(),
            'preview' => $preview,
            'company' => config('app.name'),
            'logo' => $logo ? asset($logo) : null,
            'loginUrl' => route('login'),
            'prefill' => $preview ? ['login' => '', 'password' => ''] : [
                'login' => (string) config('auth.login_prefill.login'),
                'password' => (string) config('auth.login_prefill.password'),
            ],
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function reject(string $message): never
    {
        Auth::guard('web')->logout();

        throw ValidationException::withMessages(['login' => $message]);
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
