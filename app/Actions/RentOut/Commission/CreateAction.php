<?php

namespace App\Actions\RentOut\Commission;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOut;
use App\Models\RentOutCommission;

class CreateAction
{
    public function execute(array $data, int $userId): array
    {
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);

            $data = CommissionData::normalise($rentOut, $data);
            $data['tenant_id'] = $rentOut->tenant_id;
            $data['branch_id'] = $rentOut->branch_id;
            $data['status'] = CommissionStatus::Draft->value;
            $data['created_by'] = $userId;

            validationHelper(RentOutCommission::rules(), $data);
            $model = RentOutCommission::create($data);

            $return['success'] = true;
            $return['message'] = 'Commission added.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
