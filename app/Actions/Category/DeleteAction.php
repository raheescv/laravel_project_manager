<?php

namespace App\Actions\Category;

use App\Models\Category;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Category::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Category. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
