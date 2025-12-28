<?php

namespace App\Actions\Settings\MeasurementCategory;

use App\Models\MeasurementCategory;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = MeasurementCategory::find($id);

            if (! $model) {
                throw new \Exception("Measurement Category not found with the specified ID: $id.");
            }

            if (! $model->delete()) {
                throw new \Exception(
                    'Oops! Something went wrong while deleting the Measurement Category. Please try again.'
                );
            }

            $return['success'] = true;
            $return['message'] = 'Successfully deleted Measurement Category';
            $return['data'] = $model;

        } catch (\Throwable $th) {

            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
