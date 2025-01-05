<?php

namespace App\Actions\Account;

use App\Models\Account;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Account::rules(), $data);
            $model = Account::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Account';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
