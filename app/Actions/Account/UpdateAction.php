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
            $data['mobile'] = $data['mobile'] ?? null;
            $name = $data['account_type'] . '-' . $data['name'] . '-' . $data['mobile'];
            $existing = Account::where('account_type', $data['account_type'])
                ->where('name', $data['name'])
                ->where('mobile', $data['mobile'])
                ->ignore($id)
                ->exists();
            if ($existing) {
                throw new \Exception($name . ' Already Exists', 1);
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
