<?php

namespace App\Actions\Settings\TailoringCategoryModel;

use App\Models\TailoringCategoryModel;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = TailoringCategoryModel::find($id);
            if (! $model) {
                throw new Exception("Category Model not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Category Model. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Category Model';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
