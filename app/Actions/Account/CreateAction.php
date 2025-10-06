<?php

namespace App\Actions\Account;

use App\Models\Account;

class CreateAction
{
    public function execute($data): array
    {
        try {
            $data['mobile'] = $data['mobile'] ?? null;
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
