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
     * Authenticate a staff member and issue a Sanctum token.
     *
     * Handles BOTH login methods through one action:
     *  - "pin" (default): matches the 4–6 digit MPIN.
     *  - "password": matches username (email / code / mobile) + password.
     *
     * The method is taken from the request's `method` field, defaulting to "pin"
     * when nothing is passed (inferred as "password" when a username is present).
     * Either way the token is issued with the same admin/mobile ability.
     */
    public function execute(LoginRequest $request): array
    {
        $user = $request->resolvedMethod() === 'password'
            ? $this->byCredentials((string) $request->validated('username'), (string) $request->validated('password'))
            : $this->byPin((string) $request->validated('pin'));

        $guard = $user->is_admin ? 'admin' : 'mobile';
        $token = $user->createToken($guard, [$guard])->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AuthUserResource($user),
        ];
    }

    /**
     * PINs are stored hashed, so candidates are filtered by role/active state and
     * verified one-by-one. A login is only valid when exactly one user matches.
     */
    private function byPin(string $pin): User
    {
        $candidates = User::query()
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->get();

        $matched = $candidates->filter(fn (User $user) => Hash::check($pin, $user->pin));

        if ($matched->count() !== 1) {
            throw new AuthenticationException('The provided PIN does not match our records.');
        }

        return $matched->first();
    }

    /**
     * Match an active user by email, code or mobile, then verify the password.
     */
    private function byCredentials(string $username, string $password): User
    {
        $username = trim($username);

        $user = User::query()
            ->where('is_active', true)
            ->whereNotNull('password')
            ->where(function ($query) use ($username) {
                $query->where('email', $username)
                    ->orWhere('code', $username)
                    ->orWhere('mobile', $username);
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('The provided credentials do not match our records.');
        }

        return $user;
    }
}
