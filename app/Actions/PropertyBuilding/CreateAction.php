<?php

namespace App\Actions\PropertyBuilding;

use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(PropertyBuilding::rules(), $data, 'Property Building');

            $group = PropertyGroup::find($data['property_group_id']);
            if (! $group) {
                throw new \Exception('Property Group not found.', 1);
            }

            $exists = PropertyBuilding::withTrashed()
                ->where('name', $data['name'])
                ->where('property_group_id', $data['property_group_id'])
                ->first();
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = PropertyBuilding::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Property Building';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
