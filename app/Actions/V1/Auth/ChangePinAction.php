<?php

namespace App\Actions\V1\Auth;

use App\Http\Requests\V1\Auth\ChangePinRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class ChangePinAction
{
    /**
     * Verify the authenticated employee's current PIN and replace it with a new one.
     *
     * PINs are stored hashed, so the current PIN is verified with Hash::check
     * before the new PIN is persisted (the model cast re-hashes on save).
     */
    public function execute(ChangePinRequest $request): void
    {
        $user = $request->user();

        if (! $user->pin || ! Hash::check($request->validated('current_pin'), $user->pin)) {
            throw new AuthenticationException('The provided current PIN does not match our records.');
        }

        $user->forceFill([
            'pin' => $request->validated('new_pin'),
        ])->save();
    }
}
