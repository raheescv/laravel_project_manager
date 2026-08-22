<?php

namespace App\Actions\Settings\Holiday;

use App\Models\Holiday;

class UpdateAction
{
    public function execute($data, $id, $userId)
    {
        try {
            $data['updated_by'] = $userId;

            validationHelper(Holiday::rules($id), $data);

            $model = Holiday::findOrFail($id);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully updated the holiday';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
