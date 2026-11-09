<?php

namespace App\Actions\Student\Registration;

use App\Models\Guardian;
use App\Models\StudentDetail;
use Exception;

/**
 * Make a family's pending students real: each becomes active and a pending parent
 * can then be invited to the portal. The caller owns the transaction.
 */
class ApproveAction
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

            StudentDetail::whereIn('account_id', $accountIds)
                ->where('status', StudentDetail::PENDING)
                ->get()
                ->each(fn (StudentDetail $detail) => $detail->update(['status' => 'active', 'updated_by' => $userId]));

            if ($guardian->isPendingApproval()) {
                $guardian->update(['status' => Guardian::ACTIVE, 'updated_by' => $userId]);
            }

            $return['success'] = true;
            $return['message'] = $accountIds->count() === 1 ? 'Student added' : $accountIds->count().' students added';
            $return['data'] = $guardian;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
