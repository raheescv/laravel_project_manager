<?php

namespace App\Actions\RentOut\Service;

use App\Models\RentOutService;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutService::find($id);
            if (! $model) {
                throw new \Exception("RentOut Service not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Service. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Service';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
