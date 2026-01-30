<?php

namespace App\Actions\Settings\TailoringMeasurementOption;

use App\Models\TailoringMeasurementOption;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = TailoringMeasurementOption::find($id);
            if (! $model) {
                throw new \Exception("Measurement Option not found with the specified ID: $id.", 1);
            }
            $optionType = $data['option_type'] ?? $model->option_type;
            validationHelper(TailoringMeasurementOption::rules($id, $optionType), $data, 'TailoringMeasurementOption');
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Measurement Option';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
