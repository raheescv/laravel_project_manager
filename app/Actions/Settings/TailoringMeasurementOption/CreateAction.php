<?php

namespace App\Actions\Settings\TailoringMeasurementOption;

use App\Models\TailoringMeasurementOption;

class CreateAction
{
    public function execute($data)
    {
        try {
            $optionType = $data['option_type'] ?? null;
            validationHelper(TailoringMeasurementOption::rules(0, $optionType), $data, 'TailoringMeasurementOption');
            $model = TailoringMeasurementOption::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Measurement Option';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
