<?php

namespace App\Actions\Settings\TailoringMeasurementOption;

use App\Models\TailoringMeasurementOption;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = TailoringMeasurementOption::find($id);
            if (! $model) {
                throw new Exception("Measurement Option not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Measurement Option. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Measurement Option';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
