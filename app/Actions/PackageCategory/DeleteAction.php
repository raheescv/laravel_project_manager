<?php

namespace App\Actions\PackageCategory;

use App\Models\PackageCategory;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PackageCategory::find($id);
            if (! $model) {
                throw new Exception("Package Category not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Package Category. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Package Category';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
