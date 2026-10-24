<?php

namespace App\Actions\V1\Auth;

use App\Http\Requests\V1\Auth\ChangePinRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;

class ChangePinAction
{
    /**
     * Verify the authenticated employee's current PIN and replace it with a new one.
     *
     * User::checkPin() verifies the current PIN (encrypted or legacy hashed); the
     * model's `pin` mutator encrypts the new one on save.
     */
    public function execute(ChangePinRequest $request): void
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->pin || ! $user->checkPin((string) $request->validated('current_pin'))) {
            throw new AuthenticationException('The provided current PIN does not match our records.');
        }

        $user->forceFill([
            'pin' => $request->validated('new_pin'),
        ])->save();
    }
}
