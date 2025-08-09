<?php

namespace App\Actions\Settings\Brand;

use App\Models\Brand;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(Brand::rules(), $data);
            $exists = Brand::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = tap($exists)->restore();
            } else {
                $model = Brand::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Brand';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

