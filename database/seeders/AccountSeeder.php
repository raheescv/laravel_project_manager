<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // DB::table('accounts')->truncate();

        // Create Master Groups (Top Level Categories)
        $currentAssetMaster = AccountCategory::firstOrCreate(['name' => 'Current Asset']);
        $currentLiabilityMaster = AccountCategory::firstOrCreate(['name' => 'Current Liabilities']);
        $directIncomeMaster = AccountCategory::firstOrCreate(['name' => 'Direct Income']);
        $indirectIncomeMaster = AccountCategory::firstOrCreate(['name' => 'Indirect Income']);
        $directExpenseMaster = AccountCategory::firstOrCreate(['name' => 'Direct Expense']);
        $indirectExpenseMaster = AccountCategory::firstOrCreate(['name' => 'Indirect Expense']);

        // Create Groups under Current Asset
        $cashGroup = AccountCategory::firstOrCreate(['name' => 'Cash', 'parent_id' => $currentAssetMaster->id]);
        $bankGroup = AccountCategory::firstOrCreate(['name' => 'Bank', 'parent_id' => $currentAssetMaster->id]);
        $accountReceivableGroup = AccountCategory::firstOrCreate(['name' => 'Account Receivable', 'parent_id' => $currentAssetMaster->id]);
        $stockGroup = AccountCategory::firstOrCreate(['name' => 'Stock', 'parent_id' => $currentAssetMaster->id]);

        // Create Groups under Current Liabilities
        $provisionForTaxationGroup = AccountCategory::firstOrCreate(['name' => 'Provision for Taxation', 'parent_id' => $currentLiabilityMaster->id]);

        // Create Groups under Direct Income
        $salesGroup = AccountCategory::firstOrCreate(['name' => 'Sales', 'parent_id' => $directIncomeMaster->id]);
        $purchaseReturnGroup = AccountCategory::firstOrCreate(['name' => 'Purchase Return', 'parent_id' => $directIncomeMaster->id]);

        // Create Groups under Indirect Income
        $discountReceivedGroup = AccountCategory::firstOrCreate(['name' => 'Discount Received', 'parent_id' => $indirectIncomeMaster->id]);
        $roundOffReceivedGroup = AccountCategory::firstOrCreate(['name' => 'Round Off Received', 'parent_id' => $indirectIncomeMaster->id]);

        // Create Groups under Direct Expense
        $purchaseGroup = AccountCategory::firstOrCreate(['name' => 'Purchase', 'parent_id' => $directExpenseMaster->id]);
        $salesReturnGroup = AccountCategory::firstOrCreate(['name' => 'Sales Return', 'parent_id' => $directExpenseMaster->id]);

        // Create Groups under Indirect Expense
        $discountPaidGroup = AccountCategory::firstOrCreate(['name' => 'Discount Paid', 'parent_id' => $indirectExpenseMaster->id]);

        $data = [];

        // Asset accounts - mapped to specific groups
        $data[] = ['name' => 'Cash', 'account_type' => 'asset', 'description' => 'Physical currency and cash equivalents', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $cashGroup->id];
        $data[] = ['name' => 'Card', 'account_type' => 'asset', 'description' => 'Credit and debit card transactions', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $bankGroup->id];
        $data[] = ['name' => 'General Customer', 'account_type' => 'asset', 'description' => 'Account for walk-in and general customer transactions', 'model' => 'customer', 'second_reference_no' => 2, 'account_category_id' => $accountReceivableGroup->id];
        $data[] = ['name' => 'Inventory', 'account_type' => 'asset', 'description' => 'Value of goods held for sale or production', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $stockGroup->id];

        // Direct Income accounts
        $data[] = ['name' => 'Sale', 'account_type' => 'income', 'description' => 'Sales Revenue from business operations', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $salesGroup->id];
        $data[] = ['name' => 'Purchase Returns', 'account_type' => 'income', 'description' => 'Credits received for returned purchases', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseReturnGroup->id];
        $data[] = ['name' => 'Purchase Discount', 'account_type' => 'income', 'description' => 'Discounts received on purchases', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $discountReceivedGroup->id];

        // Indirect Income accounts
        $data[] = ['name' => 'Round Off', 'account_type' => 'income', 'description' => 'Rounding adjustments for sales and payments', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $roundOffReceivedGroup->id];

        // Direct Expense accounts
        $data[] = ['name' => 'Purchase', 'account_type' => 'expense', 'description' => 'Expenses related to inventory and goods procurement', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseGroup->id];
        $data[] = ['name' => 'Cost of Goods Sold', 'account_type' => 'expense', 'description' => 'Direct costs of producing goods sold by the business', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseGroup->id];
        $data[] = ['name' => 'Freight', 'account_type' => 'expense', 'description' => 'Transportation and logistics costs for goods', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseGroup->id];
        $data[] = ['name' => 'Sales Returns', 'account_type' => 'expense', 'description' => 'Sales Returns & Allowances for refunded or returned items', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $salesReturnGroup->id];

        // Indirect Expense accounts
        $data[] = ['name' => 'Discount', 'account_type' => 'expense', 'description' => 'Sales discounts and promotional reductions', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $discountPaidGroup->id];

        // Liability accounts
        $data[] = ['name' => 'Tax Amount', 'account_type' => 'liability', 'description' => 'Sales and purchase tax liabilities', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $provisionForTaxationGroup->id];

        foreach ($data as $value) {
            $value['is_locked'] = 1;
            $exists = DB::table('accounts')
                ->where('name', $value['name'])
                ->where('account_type', $value['account_type'])
                ->exists();
            if (! $exists) {
                echo $value['name']." Created \n";
                DB::table('accounts')->insert($value);
            } else {
                // need to update the fields if the account already exists
                DB::table('accounts')
                    ->where('name', $value['name'])
                    ->where('account_type', $value['account_type'])
                    ->update($value);
            }
        }
    }
}
