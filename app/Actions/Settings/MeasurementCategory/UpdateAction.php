<?php

namespace App\Actions\Settings\MeasurementCategory;

use App\Models\MeasurementCategory;

class UpdateAction
{
    public $data;

    public function execute($data, $id)
    {
        try {
            $data['name'] = trim($data['name']);
            $this->data = $data;

            $model = MeasurementCategory::find($id);
            if (! $model) {
                throw new \Exception("Measurement Category not found with ID: $id", 1);
            }

            $this->parentCreate();

            validationHelper(MeasurementCategory::rules($id), $this->data);

            $model->update($this->data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Measurement Category';
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
            $parent = str_replace('add ', '', $this->data['parent_id']);
            $this->data['parent_id'] = MeasurementCategory::parentCreate($parent);
        }
    }
}
