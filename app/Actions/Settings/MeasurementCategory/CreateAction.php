<?php

namespace App\Actions\Settings\MeasurementCategory;

use App\Models\MeasurementCategory;

class CreateAction
{
    public $data;

    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            $this->data = $data;

            // If parent category can be created on the fly
            $this->parentCreate();

            // Validation helper (you can create a rules method in MeasurementCategory model)
            validationHelper(MeasurementCategory::rules(), $this->data, 'Measurement Category');

            // Create the MeasurementCategory
            $model = MeasurementCategory::create($this->data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Model';
            $return['data'] = $model;

        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function parentCreate()
    {
        if (isset($this->data['parent_id']) && str_contains($this->data['parent_id'], 'add ')) {
            $parentName = str_replace('add ', '', $this->data['parent_id']);
            $this->data['parent_id'] = MeasurementCategory::parentCreate($parentName);
        }
    }
}
