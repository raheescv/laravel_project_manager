<?php

namespace App\Actions\Settings\TailoringCategory;

use App\Models\TailoringCategory;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(TailoringCategory::rules(), $data, 'TailoringCategory');
            $model = TailoringCategory::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Tailoring Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
