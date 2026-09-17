<?php

namespace App\Actions\Parent;

use App\Models\Account;
use App\Models\Guardian;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The one door from a parent to a student: the student must be linked to this
 * parent in guardian_student. Anything else is a 404, never a 403, so the portal
 * does not confirm that someone else's child exists.
 */
class FindStudentAction
{
    public function execute(Guardian $guardian, int|string $accountId): Account
    {
        $account = $guardian->students()
            ->with('studentDetail')
            ->where('accounts.id', (int) $accountId)
            ->first();

        if (! $account) {
            throw new NotFoundHttpException();
        }

        return $account;
    }
}
