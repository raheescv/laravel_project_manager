<?php

namespace App\Actions\V1\Auth;

use App\Http\Requests\V1\Auth\ChangePasswordRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class ChangePasswordAction
{
    /**
     * Verify the authenticated user's current password and replace it with a new one.
     *
     * Passwords are stored hashed, so the current password is verified with
     * Hash::check before the new password is persisted (the model cast re-hashes
     * on save).
     */
    public function execute(ChangePasswordRequest $request): void
    {
        $user = $request->user();

        if (! $user->password || ! Hash::check($request->validated('current_password'), $user->password)) {
            throw new AuthenticationException('The provided current password does not match our records.');
        }

        $user->forceFill([
            'password' => $request->validated('new_password'),
        ])->save();
    }
}
