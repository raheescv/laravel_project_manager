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

        // Eager-load everything AuthUserResource reads so serialization doesn't
        // fire lazy queries per field (permissions/roles/designation).
        $user->load(['roles.permissions', 'permissions', 'designation']);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AuthUserResource($user),
        ];
    }

    /**
     * A PIN carries no username, so the user has to be found by the PIN itself.
     * Doing that with bcrypt alone means one Hash::check per active user — at
     * BCRYPT_ROUNDS=12 that is ~266ms each, so a tenant with 149 PIN users spent
     * ~40 seconds on every sign-in.
     *
     * `pin_lookup` (see User::pinLookup) is an indexed keyed digest of the PIN, so
     * the candidate is found with one query and costs a single bcrypt verify. The
     * digest only narrows the search — the bcrypt hash is still what authenticates.
     *
     * A login is only valid when exactly one user matches.
     */
    private function byPin(string $pin): User
    {
        $matched = User::query()
            ->where('is_active', true)
            ->where('pin_lookup', User::pinLookup($pin))
            ->get(['id', 'pin'])
            ->filter(fn (User $user) => Hash::check($pin, $user->pin));

        if ($matched->count() > 1) {
            throw new AuthenticationException('The provided PIN does not match our records.');
        }

        if ($matched->count() === 1) {
            return User::findOrFail($matched->first()->id);
        }

        return $this->byPinLegacy($pin);
    }

    /**
     * Fallback for users whose PIN predates `pin_lookup`. A bcrypt hash is one-way,
     * so the digest could not be backfilled by the migration — each user is migrated
     * here the first time they sign in, and this set only ever shrinks.
     *
     * Scanning is deliberately limited to rows still missing a digest: that bounds
     * what a mistyped PIN costs, which matters on a till where typos are routine.
     *
     * Only id+pin are pulled so we don't hydrate every column (and the audit/cast
     * overhead) for every candidate; the winner is then loaded in full.
     */
    private function byPinLegacy(string $pin): User
    {
        $matched = User::query()
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->whereNull('pin_lookup')
            ->get(['id', 'pin'])
            ->filter(fn (User $user) => Hash::check($pin, $user->pin));

        if ($matched->count() !== 1) {
            throw new AuthenticationException('The provided PIN does not match our records.');
        }

        $user = User::findOrFail($matched->first()->id);

        // Backfill the digest only — writing `pin` would re-hash an already-hashed
        // value. Quietly, so a sign-in doesn't generate an audit revision.
        $user->forceFill(['pin_lookup' => User::pinLookup($pin)])->saveQuietly();

        return $user;
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
