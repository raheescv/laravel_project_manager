<?php

namespace App\Actions\Settings\MeasurementSubCategory;

use App\Models\MeasurementSubCategory;

class CreateAction
{
    public function execute(array $data)
    {
        $subcategory = MeasurementSubCategory::create([
            'name' => $data['name'],
            'measurement_category_id' => $data['measurement_category_id'],
        ]);

        return [
            'success' => true,
            'message' => 'Subcategory created successfully',
            'data' => $subcategory,
        ];
    }
}
