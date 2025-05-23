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

        $data[] = ['name' => 'Cash', 'account_type' => 'asset', 'description' => null, 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Card', 'account_type' => 'asset', 'description' => null, 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'General Customer', 'account_type' => 'asset', 'description' => null, 'model' => 'customer', 'second_reference_no' => 2];
        $data[] = ['name' => 'Sale', 'account_type' => 'income', 'description' => 'Sales Revenue', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Sales Returns', 'account_type' => 'expense', 'description' => 'Sales Returns & Allowances', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Purchase', 'account_type' => 'expense', 'description' => 'Purchase', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Tax Amount', 'account_type' => 'liability', 'description' => 'Tax', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Discount', 'account_type' => 'expense', 'description' => 'Discounts', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Purchase Discount', 'account_type' => 'income', 'description' => 'Purchase Discounts', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Freight', 'account_type' => 'expense', 'description' => 'Cost of transportation or logistics', 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Inventory', 'account_type' => 'asset', 'description' => null, 'model' => null, 'second_reference_no' => null];
        $data[] = ['name' => 'Cost of Goods Sold', 'account_type' => 'expense', 'description' => null, 'model' => null, 'second_reference_no' => null];
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
