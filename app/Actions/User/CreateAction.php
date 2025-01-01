<?php

namespace App\Actions\User;

use App\Models\User;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(User::createRules(), $data);
            $model = User::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created User';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
