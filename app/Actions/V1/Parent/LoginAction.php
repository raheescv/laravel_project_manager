<?php

namespace App\Actions\V1\Parent;

use App\Actions\Student\Guardian\SyncAction;
use App\Http\Requests\V1\Parent\LoginRequest;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * A parent signs in with the mobile number the school has for them (or their
 * email) and the password they chose from the invite link.
 */
class LoginAction
{
    public function execute(LoginRequest $request): array
    {
        $login = trim((string) $request->validated('login'));

        $key = 'parent-login:'.$request->ip().'|'.mb_strtolower($login);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->status(429);
        }

        $guardian = str_contains($login, '@')
            ? Guardian::where('email', $login)->first()
            : Guardian::where('mobile', SyncAction::normalizeMobile($login))->first();

        if (! $guardian || ! $guardian->password || ! $guardian->isActive() || ! Hash::check((string) $request->validated('password'), $guardian->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['login' => $guardian && ! $guardian->password
                ? 'You have not set a password yet. Use the link the school sent you, or tap "Forgot password".'
                : 'The mobile number / email or password is not correct.']);
        }

        RateLimiter::clear($key);

        return (new IssueTokenAction())->execute($guardian, (bool) $request->validated('remember', true));
    }
}
