<?php

namespace App\Actions\Settings\Branch;

use App\Models\Branch;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Branch::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }

            validationHelper(Branch::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Branch';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
