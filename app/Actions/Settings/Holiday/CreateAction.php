<?php

namespace App\Actions\Settings\Holiday;

use App\Models\Holiday;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $userId;

            validationHelper(Holiday::rules(), $data);

            $model = Holiday::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully added the holiday';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
