<?php

namespace App\Actions\Account;

use App\Models\Account;
use App\Models\AccountCategory;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Account::find($id);
            if (! $model) {
                throw new \Exception("Account not found with the specified ID: $id.", 1);
            }

            if (isset($data['account_category_id']) && str_contains($data['account_category_id'], 'add ')) {
                $name = str_replace('add ', '', $data['account_category_id']);
                $data['account_category_id'] = AccountCategory::selfCreate($name);
            }

            $data['mobile'] = $data['mobile'] ?? null;
            $name = $data['account_type'].'-'.$data['name'].'-'.$data['mobile'];
            $existing = Account::where('account_type', $data['account_type'])
                ->where('name', $data['name'])
                ->where('mobile', $data['mobile'])
                ->whereNot('id', $id)
                ->exists();
            if ($existing) {
                throw new \Exception($name.' Already Exists', 1);
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
