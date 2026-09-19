<?php

namespace App\Actions\V1\Parent;

use App\Http\Requests\V1\Parent\ChangePasswordRequest;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * A signed-in parent chooses a new password. The current one must be given, so a
 * phone left unlocked is not enough. The parent stays signed in where they made
 * the change; every other sign-in ends, as after a reset link.
 */
class ChangePasswordAction
{
    public function execute(ChangePasswordRequest $request): void
    {
        /** @var Guardian $guardian */
        $guardian = $request->user('parent');

        $key = 'parent-password:'.$guardian->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['current_password' => 'Too many tries. Try again in '.RateLimiter::availableIn($key).' seconds.'])->status(429);
        }

        if (! $guardian->password || ! Hash::check((string) $request->validated('current_password'), $guardian->password)) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        RateLimiter::clear($key);

        $guardian->forceFill(['password' => (string) $request->validated('password')])->save();
        $guardian->tokens()->whereKeyNot($guardian->currentAccessToken()->getKey())->delete();
    }
}
