<?php

namespace App\Actions\Account;

use App\Models\Account;
use App\Models\AccountCategory;

class CreateAction
{
    public function execute($data): array
    {
        try {
            $data['mobile'] = $data['mobile'] ?? null;

            if (isset($data['account_category_id']) && str_contains($data['account_category_id'], 'add ')) {
                $name = str_replace('add ', '', $data['account_category_id']);
                $data['account_category_id'] = AccountCategory::selfCreate($name);
            }

            $name = $data['account_type'].'-'.$data['name'].'-'.$data['mobile'];
            $existing = Account::where('account_type', $data['account_type'])
                ->where('name', $data['name'])
                ->where('mobile', $data['mobile'])
                ->exists();
            if ($existing) {
                throw new \Exception($name.' Already Exists', 1);
            }
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
