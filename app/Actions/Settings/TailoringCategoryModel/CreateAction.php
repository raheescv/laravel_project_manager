<?php

namespace App\Actions\Settings\TailoringCategoryModel;

use App\Models\TailoringCategoryModel;

class CreateAction
{
    public function execute($data)
    {
        try {
            $categoryId = $data['tailoring_category_id'] ?? null;
            validationHelper(TailoringCategoryModel::rules(0, $categoryId), $data, 'TailoringCategoryModel');
            $model = TailoringCategoryModel::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Category Model';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
