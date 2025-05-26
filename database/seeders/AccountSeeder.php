<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // DB::table('accounts')->truncate();
        $data = [];

        $data[] = ['name' => 'Cash', 'account_type' => 'asset', 'description' => 'Physical currency and cash equivalents', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Card', 'account_type' => 'asset', 'description' => 'Credit and debit card transactions', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'General Customer', 'account_type' => 'asset', 'description' => 'Account for walk-in and general customer transactions', 'model' => 'customer', 'second_reference_no' => 2];
        $data[] = ['name' => 'Sale', 'account_type' => 'income', 'description' => 'Sales Revenue from business operations', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Sales Returns', 'account_type' => 'expense', 'description' => 'Sales Returns & Allowances for refunded or returned items', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Purchase', 'account_type' => 'expense', 'description' => 'Expenses related to inventory and goods procurement', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Purchase Returns', 'account_type' => 'income', 'description' => 'Credits received for returned purchases', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Tax Amount', 'account_type' => 'liability', 'description' => 'Sales and purchase tax liabilities', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Discount', 'account_type' => 'expense', 'description' => 'Sales discounts and promotional reductions', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Purchase Discount', 'account_type' => 'income', 'description' => 'Discounts received on purchases', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Freight', 'account_type' => 'expense', 'description' => 'Transportation and logistics costs for goods', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Inventory', 'account_type' => 'asset', 'description' => 'Value of goods held for sale or production', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Cost of Goods Sold', 'account_type' => 'expense', 'description' => 'Direct costs of producing goods sold by the business', 'model' => null, 'second_reference_no' => null];
        foreach ($data as $value) {
            $value['is_locked'] = 1;
            $exists = DB::table('accounts')
                ->where('name', $value['name'])
                ->where('account_type', $value['account_type'])
                ->exists();
            if (! $exists) {
                echo $value['name']." Created \n";
                DB::table('accounts')->insert($value);
            }
        }
    }
}
