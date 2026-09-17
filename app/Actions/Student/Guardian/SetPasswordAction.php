<?php

namespace App\Actions\Student\Guardian;

use App\Models\Guardian;
use Exception;

/**
 * Redeem a set-password link: the token must match an active parent and be unexpired.
 * The token is burned on use, so the link works exactly once, and every portal
 * sign-in the parent already had is ended: a reset is often the answer to a
 * lost phone.
 */
class SetPasswordAction
{
    public function execute(string $token, string $password): array
    {
        try {
            $guardian = self::findByToken($token);
            if (! $guardian) {
                throw new Exception('This link has expired or was already used. Ask the school to send a new one.', 1);
            }

            validationHelper(['password' => ['required', 'string', 'min:8', 'max:100']], ['password' => $password]);

            $guardian->forceFill([
                'password' => $password,
                'invite_token_hash' => null,
                'invite_expires_at' => null,
            ])->save();
            $guardian->tokens()->delete();

            $return['success'] = true;
            $return['message'] = 'Password saved';
            $return['data'] = $guardian;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public static function findByToken(string $token): ?Guardian
    {
        if (strlen($token) < 20) {
            return null;
        }

        return Guardian::where('invite_token_hash', hash('sha256', $token))
            ->where('invite_expires_at', '>', now())
            ->where('status', 'active')
            ->first();
    }
}
