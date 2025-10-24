<?php

namespace App\Actions\Settings\Brand;

use App\Models\Brand;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Brand::find($id);
            if (! $model) {
                throw new \Exception("Brand not found with the specified ID: $id.", 1);
            }

            // Delete associated image file if exists
            $model->deleteImage();

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Brand. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Brand';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
