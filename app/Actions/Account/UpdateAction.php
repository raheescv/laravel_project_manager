<?php

namespace App\Actions\Account;

use App\Models\Account;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Account::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(Account::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Account';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
