<?php

namespace App\Actions\Property;

use App\Models\Property;
use App\Models\PropertyBuilding;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['number'] = trim($data['number']);
            validationHelper(Property::rules(), $data, 'Property');

            $building = PropertyBuilding::find($data['property_building_id']);
            if (! $building) {
                throw new \Exception('Property Building not found.', 1);
            }

            $exists = Property::withTrashed()
                ->where('number', $data['number'])
                ->where('property_building_id', $data['property_building_id'])
                ->first();
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = Property::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Property';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
