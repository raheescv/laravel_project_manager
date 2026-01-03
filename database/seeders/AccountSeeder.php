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
        $currentAssetMaster = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Current Asset']);
        $currentLiabilityMaster = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Current Liabilities']);
        $directIncomeMaster = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Direct Income']);
        $indirectIncomeMaster = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Indirect Income']);
        $directExpenseMaster = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Direct Expense']);
        $indirectExpenseMaster = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Indirect Expense']);

        // Create Groups under Current Asset
        $cashGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Cash', 'parent_id' => $currentAssetMaster->id]);
        $bankGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Bank', 'parent_id' => $currentAssetMaster->id]);
        $accountReceivableGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Account Receivable', 'parent_id' => $currentAssetMaster->id]);
        $stockGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Stock', 'parent_id' => $currentAssetMaster->id]);

        // Create Groups under Current Liabilities
        $provisionForTaxationGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Provision for Taxation', 'parent_id' => $currentLiabilityMaster->id]);

        // Create Groups under Direct Income
        $salesGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Sales', 'parent_id' => $directIncomeMaster->id]);
        $purchaseReturnGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Purchase Return', 'parent_id' => $directIncomeMaster->id]);

        // Create Groups under Indirect Income
        $discountReceivedGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Discount Received', 'parent_id' => $indirectIncomeMaster->id]);
        $roundOffReceivedGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Round Off Received', 'parent_id' => $indirectIncomeMaster->id]);

        // Create Groups under Direct Expense
        $purchaseGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Purchase', 'parent_id' => $directExpenseMaster->id]);
        $salesReturnGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Sales Return', 'parent_id' => $directExpenseMaster->id]);

        // Create Groups under Indirect Expense
        $discountPaidGroup = AccountCategory::firstOrCreate(['tenant_id' => 1, 'name' => 'Discount Paid', 'parent_id' => $indirectExpenseMaster->id]);

        $data = [];

        // Asset accounts - mapped to specific groups
        $data[] = ['name' => 'Cash', 'slug' => 'cash', 'account_type' => 'asset', 'description' => 'Physical currency and cash equivalents', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $cashGroup->id];
        $data[] = ['name' => 'Card', 'slug' => 'card', 'account_type' => 'asset', 'description' => 'Credit and debit card transactions', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $bankGroup->id];
        $data[] = ['name' => 'General Customer', 'slug' => 'general_customer', 'account_type' => 'asset', 'description' => 'Account for walk-in and general customer transactions', 'model' => 'customer', 'second_reference_no' => 2, 'account_category_id' => $accountReceivableGroup->id];
        $data[] = ['name' => 'Inventory', 'slug' => 'inventory', 'account_type' => 'asset', 'description' => 'Value of goods held for sale or production', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $stockGroup->id];

        // Direct Income accounts
        $data[] = ['name' => 'Sale', 'slug' => 'sale', 'account_type' => 'income', 'description' => 'Sales Revenue from business operations', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $salesGroup->id];
        $data[] = ['name' => 'Purchase Returns', 'slug' => 'purchase_returns', 'account_type' => 'income', 'description' => 'Credits received for returned purchases', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseReturnGroup->id];
        $data[] = ['name' => 'Purchase Discount', 'slug' => 'purchase_discount', 'account_type' => 'income', 'description' => 'Discounts received on purchases', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $discountReceivedGroup->id];

        // Indirect Income accounts
        $data[] = ['name' => 'Round Off', 'slug' => 'round_off', 'account_type' => 'income', 'description' => 'Rounding adjustments for sales and payments', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $roundOffReceivedGroup->id];

        // Direct Expense accounts
        $data[] = ['name' => 'Purchase', 'slug' => 'purchase', 'account_type' => 'expense', 'description' => 'Expenses related to inventory and goods procurement', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseGroup->id];
        $data[] = ['name' => 'Cost of Goods Sold', 'slug' => 'cost_of_goods_sold', 'account_type' => 'expense', 'description' => 'Direct costs of producing goods sold by the business', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseGroup->id];
        $data[] = ['name' => 'Freight', 'slug' => 'freight', 'account_type' => 'expense', 'description' => 'Transportation and logistics costs for goods', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $purchaseGroup->id];
        $data[] = ['name' => 'Sales Returns', 'slug' => 'sales_returns', 'account_type' => 'expense', 'description' => 'Sales Returns & Allowances for refunded or returned items', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $salesReturnGroup->id];

        // Indirect Expense accounts
        $data[] = ['name' => 'Discount', 'slug' => 'discount', 'account_type' => 'expense', 'description' => 'Sales discounts and promotional reductions', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $discountPaidGroup->id];

        // Liability accounts
        $data[] = ['name' => 'Tax Amount', 'slug' => 'tax_amount', 'account_type' => 'liability', 'description' => 'Sales and purchase tax liabilities', 'model' => null, 'second_reference_no' => null, 'account_category_id' => $provisionForTaxationGroup->id];

        foreach ($data as $value) {
            $value['tenant_id'] = 1;
            $value['is_locked'] = 1;
            $exists = DB::table('accounts')->where('name', $value['name'])->where('account_type', $value['account_type'])->exists();
            if (! $exists) {
                echo $value['name']." Created \n";
                DB::table('accounts')->insert($value);
            } else {
                // need to update the fields if the account already exists
                DB::table('accounts')->where('name', $value['name'])->where('account_type', $value['account_type'])->update($value);
            }
        }
    }
}
