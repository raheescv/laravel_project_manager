<?php

namespace App\Actions\RentOut\Service;

use App\Models\RentOutService;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = RentOutService::find($id);
            if (! $model) {
                throw new \Exception("RentOut Service not found with the specified ID: $id.", 1);
            }
            validationHelper(RentOutService::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Service';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
