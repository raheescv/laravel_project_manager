<?php

namespace App\Actions\Settings\TailoringCategoryModelType;

use App\Models\TailoringCategoryModelType;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = TailoringCategoryModelType::find($id);
            if (! $model) {
                throw new Exception("Category Model Type not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Category Model Type. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Category Model Type';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
