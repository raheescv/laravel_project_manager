<?php

namespace App\Actions\Settings\TailoringCategoryModelType;

use App\Models\TailoringCategory;
use App\Models\TailoringCategoryModelType;

class CreateAction
{
    public function execute($data)
    {
        try {
            $categoryId = $data['tailoring_category_id'] ?? null;
            validationHelper(TailoringCategoryModelType::rules(0, $categoryId), $data, 'TailoringCategoryModelType');

            $category = TailoringCategory::findOrFail($categoryId);
            $data['tenant_id'] = $category->tenant_id;

            $model = TailoringCategoryModelType::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Category Model Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
