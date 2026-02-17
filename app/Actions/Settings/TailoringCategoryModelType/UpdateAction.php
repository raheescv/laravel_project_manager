<?php

namespace App\Actions\Settings\TailoringCategoryModelType;

use App\Models\TailoringCategoryModelType;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = TailoringCategoryModelType::find($id);
            if (! $model) {
                throw new \Exception("Category Model Type not found with the specified ID: $id.", 1);
            }
            $categoryId = $data['tailoring_category_id'] ?? $model->tailoring_category_id;
            validationHelper(TailoringCategoryModelType::rules($id, $categoryId), $data, 'TailoringCategoryModelType');
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Category Model Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
