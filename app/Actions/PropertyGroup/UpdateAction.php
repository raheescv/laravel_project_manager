<?php

namespace App\Actions\PropertyGroup;

use App\Models\PropertyGroup;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = PropertyGroup::find($id);
            if (! $model) {
                throw new \Exception("Property Group not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(PropertyGroup::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Property Group';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
