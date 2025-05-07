<?php

namespace App\Actions\Account\Note;

use App\Models\AccountNote;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(AccountNote::rules(), $data);
            $model = AccountNote::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created AccountNote';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
