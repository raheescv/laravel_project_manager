<?php

namespace App\Actions\Settings\MeasurementSubCategory;

use App\Models\MeasurementSubCategory;

class UpdateAction
{
    public function execute(array $data, int $id)
    {
        $subcategory = MeasurementSubCategory::findOrFail($id);

        $subcategory->update([
            'name' => $data['name'],
            'measurement_category_id' => $data['measurement_category_id'],
        ]);

        return [
            'success' => true,
            'message' => 'Model updated successfully',
            'data' => $subcategory,
        ];
    }
}
