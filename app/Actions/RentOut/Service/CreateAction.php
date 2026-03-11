<?php

namespace App\Actions\RentOut\Service;

use App\Models\RentOutService;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutService::rules(), $data, 'RentOut Service');
            $model = RentOutService::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Service';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
