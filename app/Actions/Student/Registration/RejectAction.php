<?php

namespace App\Actions\Student\Registration;

use App\Actions\Student\DeleteAction;
use App\Models\Guardian;
use Exception;

/**
 * Turn down a family's registration: the pending students are removed, which frees
 * their registration numbers, and so is a parent who came only from this form.
 * Enrolled students and existing parents are never touched. The caller owns the
 * transaction.
 */
class RejectAction
{
    public function execute(int $guardianId, int $userId): array
    {
        try {
            $guardian = Guardian::query()->lockForUpdate()->find($guardianId);
            if (! $guardian) {
                throw new Exception("Parent not found with the specified ID: $guardianId.", 1);
            }

            $accountIds = $guardian->pendingStudents()->pluck('accounts.id');
            if ($accountIds->isEmpty()) {
                throw new Exception('This registration has already been handled.', 1);
            }

            foreach ($accountIds as $accountId) {
                $response = (new DeleteAction())->execute($accountId, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            if ($guardian->isPendingApproval()) {
                $guardian->delete();
            }

            $return['success'] = true;
            $return['message'] = 'Registration rejected';
            $return['data'] = $guardian;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
