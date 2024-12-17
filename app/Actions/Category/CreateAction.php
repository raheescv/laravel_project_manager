<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class CreateAction
{
    public function execute($data)
    {
        try {
            $validator = Validator::make($data, Category::rules());
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
            $model = Category::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
