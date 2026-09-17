<?php

namespace App\Actions\V1\Parent;

use App\Http\Resources\V1\Parent\GuardianResource;
use App\Models\Guardian;

/**
 * Sign a parent in to the portal app: a Sanctum token that only the `parent`
 * guard accepts (config/auth.php), expiring on its own.
 */
class IssueTokenAction
{
    /** "Keep me signed in". */
    public const REMEMBER_DAYS = 30;

    /** A shared or borrowed phone. */
    public const SESSION_HOURS = 12;

    public function execute(Guardian $guardian, bool $remember = true): array
    {
        $expiresAt = $remember ? now()->addDays(self::REMEMBER_DAYS) : now()->addHours(self::SESSION_HOURS);
        $token = $guardian->createToken('parent-portal', ['parent'], $expiresAt);
        $guardian->forceFill(['last_login_at' => now()])->saveQuietly();

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'parent' => new GuardianResource($guardian),
        ];
    }
}
