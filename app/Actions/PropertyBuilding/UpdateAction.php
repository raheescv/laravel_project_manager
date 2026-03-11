<?php

namespace App\Actions\PropertyBuilding;

use App\Models\PropertyBuilding;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = PropertyBuilding::find($id);
            if (! $model) {
                throw new \Exception("Property Building not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(PropertyBuilding::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Property Building';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
