<?php

namespace App\Actions\Settings\TailoringCategoryModel;

use App\Models\TailoringCategoryModel;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = TailoringCategoryModel::find($id);
            if (! $model) {
                throw new \Exception("Category Model not found with the specified ID: $id.", 1);
            }
            $categoryId = $data['tailoring_category_id'] ?? $model->tailoring_category_id;
            validationHelper(TailoringCategoryModel::rules($id, $categoryId), $data, 'TailoringCategoryModel');
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Category Model';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
