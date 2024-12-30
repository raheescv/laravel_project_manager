<?php

namespace App\Actions\Settings\Unit;

use App\Models\Unit;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(Unit::rules(), $data);
            $model = Unit::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Unit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }
        return $return;
    }
}
