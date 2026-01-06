<?php

namespace App\Actions\EmployeeCommission;

use App\Models\EmployeeCommission;
use Exception;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = EmployeeCommission::find($id);
            if (! $model) {
                throw new Exception("Employee Commission not found with the specified ID: $id.", 1);
            }

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Employee Commission';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
