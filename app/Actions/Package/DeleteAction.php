<?php

namespace App\Actions\Package;

use App\Models\Package;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Package::find($id);
            if (! $model) {
                throw new Exception("Package not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Package. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Package';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

