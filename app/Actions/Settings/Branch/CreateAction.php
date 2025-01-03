<?php

namespace App\Actions\Settings\Branch;

use App\Models\Branch;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Branch::rules(), $data);
            $model = Branch::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Branch';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
