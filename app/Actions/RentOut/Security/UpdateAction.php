<?php

namespace App\Actions\RentOut\Security;

use App\Models\RentOutSecurity;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = RentOutSecurity::find($id);
            if (! $model) {
                throw new \Exception("RentOut Security not found with the specified ID: $id.", 1);
            }
            validationHelper(RentOutSecurity::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Security Deposit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
