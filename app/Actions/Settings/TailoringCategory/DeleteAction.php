<?php

namespace App\Actions\Settings\TailoringCategory;

use App\Models\TailoringCategory;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = TailoringCategory::find($id);
            if (! $model) {
                throw new Exception("Tailoring Category not found with the specified ID: $id.", 1);
            }

            if ($model->models()->count() > 0) {
                throw new Exception('This category is used by tailoring models. Please remove or reassign them first.', 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Tailoring Category. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Tailoring Category';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
