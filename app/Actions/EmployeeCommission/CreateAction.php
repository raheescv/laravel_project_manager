<?php

namespace App\Actions\EmployeeCommission;

use App\Models\EmployeeCommission;

class CreateAction
{
    public function execute($data)
    {
        try {
            $model = EmployeeCommission::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Employee Commission';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
