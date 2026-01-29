<?php

namespace App\Actions\Settings\TailoringCategory;

use App\Models\TailoringCategory;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = TailoringCategory::find($id);
            if (! $model) {
                throw new \Exception("Tailoring Category not found with the specified ID: $id.", 1);
            }
            validationHelper(TailoringCategory::rules($id), $data, 'TailoringCategory');
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tailoring Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
