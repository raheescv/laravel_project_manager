<?php

namespace App\Actions\Settings\Unit;

use App\Models\Unit;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Unit::find($id);
            if (! $model) {
                throw new \Exception("Unit not found with the specified ID: $id.", 1);
            }
            validationHelper(Unit::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Unit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
