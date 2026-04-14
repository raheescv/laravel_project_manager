<?php

namespace App\Actions\RentOut\Security;

use App\Models\RentOutSecurity;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutSecurity::rules(), $data, 'RentOut Security');
            $model = RentOutSecurity::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Security Deposit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
