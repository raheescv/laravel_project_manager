<?php

namespace App\Actions\Settings\PropertyType;

use App\Models\PropertyType;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(PropertyType::rules(), $data, 'Property Type');
            $exists = PropertyType::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = PropertyType::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Property Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
