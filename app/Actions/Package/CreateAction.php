<?php

namespace App\Actions\Package;

use App\Models\Package;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Package::rules(), $data);
            $model = Package::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

