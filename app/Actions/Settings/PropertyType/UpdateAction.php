<?php

namespace App\Actions\Settings\PropertyType;

use App\Models\PropertyType;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = PropertyType::find($id);
            if (! $model) {
                throw new \Exception("Property Type not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(PropertyType::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Property Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
