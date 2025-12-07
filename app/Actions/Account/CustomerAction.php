<?php

namespace App\Actions\Account;

use App\Models\Sale;
use App\Models\Account;

class CustomerAction
{
    public function getCustomerBySaleId($sale_id)
    {
        try {
            $sale = Sale::findOrFail($sale_id);
            $customer = Account::findOrFail($sale->account_id);

            return [
                'success' => true,
                'items' => [
                    [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'mobile' => $customer->mobile,
                        'email' => $customer->email,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'items' => [],
                'message' => 'Sale or customer not found',
            ];
        }
    }
}

