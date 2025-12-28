<?php

namespace App\Actions\PackageCategory;

use App\Models\PackageCategory;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(PackageCategory::rules(), $data);
            $model = PackageCategory::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

