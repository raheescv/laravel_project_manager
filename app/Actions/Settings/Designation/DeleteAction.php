<?php

namespace App\Actions\Settings\Designation;

use App\Models\Designation;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Designation::find($id);
            if (! $model) {
                throw new Exception("Designation not found with the specified ID: $id.", 1);
            }

            if ($model->employees->count() > 0) {
                throw new Exception('This is used by Employees, so please delete the employees first or make a change.', 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Designation. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Designation';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
