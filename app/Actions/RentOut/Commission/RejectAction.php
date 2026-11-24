<?php

namespace App\Actions\RentOut\Commission;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

class RejectAction
{
    public function execute(int $id, string $reason, int $userId): array
    {
        try {
            $model = RentOutCommission::findOrFail($id);
            if (! $model->status->isRejectable()) {
                throw new Exception('Only a commission awaiting approval can be rejected.', 1);
            }
            if (trim($reason) === '') {
                throw new Exception('Please enter a reason for rejecting.', 1);
            }

            $model->update([
                'status' => CommissionStatus::Rejected,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => trim($reason),
                'ceo_approved_by' => null,
                'ceo_approved_at' => null,
                'owner_approved_by' => null,
                'owner_approved_at' => null,
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Commission rejected.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
