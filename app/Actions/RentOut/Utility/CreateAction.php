<?php

namespace App\Actions\RentOut\Utility;

use App\Models\RentOutUtility;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutUtility::rules(), $data, 'RentOut Utility');
            $model = RentOutUtility::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Utility';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
