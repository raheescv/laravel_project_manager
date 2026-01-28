<?php

namespace App\Actions\Settings\Designation;

use App\Models\Designation;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Designation::rules(), $data);
            $model = Designation::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Designation';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
