<?php

namespace App\Actions\PropertyGroup;

use App\Models\PropertyGroup;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(PropertyGroup::rules(), $data, 'Property Group');
            $exists = PropertyGroup::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = PropertyGroup::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Property Group';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
