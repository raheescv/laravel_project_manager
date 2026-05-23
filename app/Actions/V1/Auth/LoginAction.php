<?php

namespace App\Actions\V1\Auth;

use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    /**
     * Authenticate a staff member by PIN and issue a Sanctum token.
     *
     * PINs are stored hashed, so candidates are filtered by role/active state
     * and verified one-by-one. A login is only valid when exactly one user matches.
     */
    public function execute(LoginRequest $request): array
    {
        $pin = $request->validated('pin');

        $candidates = User::query()
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->get();

        $matched = $candidates->filter(fn (User $user) => Hash::check($pin, $user->pin));

        if ($matched->count() !== 1) {
            throw new AuthenticationException('The provided PIN does not match our records.');
        }

        $user = $matched->first();
        $guard = $user->is_admin ? 'admin' : 'mobile';
        $token = $user->createToken($guard, [$guard])->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AuthUserResource($user),
        ];
    }
}
