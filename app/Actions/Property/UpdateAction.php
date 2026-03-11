<?php

namespace App\Actions\Property;

use App\Models\Property;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Property::find($id);
            if (! $model) {
                throw new \Exception("Property not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(Property::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Property';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
