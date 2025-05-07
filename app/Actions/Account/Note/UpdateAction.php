<?php

namespace App\Actions\Account\Note;

use App\Models\AccountNote;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = AccountNote::find($id);
            if (! $model) {
                throw new \Exception("Account Note not found with the specified ID: $id.", 1);
            }

            validationHelper(AccountNote::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated AccountNote';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
