<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Category::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $validator = Validator::make($data, Category::rules($id));
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
