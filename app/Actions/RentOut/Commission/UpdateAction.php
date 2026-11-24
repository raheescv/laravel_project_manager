<?php

namespace App\Actions\RentOut\Commission;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

class UpdateAction
{
    /**
     * Editing is only allowed before approval starts; editing a rejected
     * commission sends it back to Draft for resubmission.
     */
    public function execute(array $data, int $id, int $userId): array
    {
        try {
            $model = RentOutCommission::with('rentOut')->findOrFail($id);
            if (! $model->status->isEditable()) {
                throw new Exception('Only a draft or rejected commission can be edited.', 1);
            }

            $data = CommissionData::normalise($model->rentOut, array_merge(['rent_out_id' => $model->rent_out_id], $data));
            $data['tenant_id'] = $model->tenant_id;
            $data['branch_id'] = $model->branch_id;
            validationHelper(RentOutCommission::rules($id), $data);

            $model->update(array_merge($data, [
                'status' => CommissionStatus::Draft,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'updated_by' => $userId,
            ]));

            $return['success'] = true;
            $return['message'] = 'Commission updated.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
