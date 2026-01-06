<?php

namespace App\Actions\EmployeeCommission;

use App\Models\EmployeeCommission;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = EmployeeCommission::find($id);
            if (! $model) {
                throw new Exception("Employee Commission not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Employee Commission. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Employee Commission';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
