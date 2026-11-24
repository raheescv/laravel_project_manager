<?php

namespace App\Actions\RentOut\Commission;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

class SubmitAction
{
    public function execute(int $id, int $userId): array
    {
        try {
            $model = RentOutCommission::findOrFail($id);
            if ($model->status !== CommissionStatus::Draft) {
                throw new Exception('Only a draft commission can be submitted.', 1);
            }

            $model->update([
                'status' => CommissionStatus::Submitted,
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Commission submitted for approval.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
