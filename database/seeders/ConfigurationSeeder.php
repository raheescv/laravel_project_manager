<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::firstOrCreate(['tenant_id' => 1, 'key' => 'barcode_type', 'value' => 'product_wise']);
        Configuration::firstOrCreate(['tenant_id' => 1, 'key' => 'contact_no', 'value' => '9633155669']);
        Configuration::firstOrCreate(['tenant_id' => 1, 'key' => 'mobile', 'value' => '9633155669']);
        $payment_methods = [1, 2];
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'payment_methods'], ['value' => json_encode($payment_methods)]);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'default_status'], ['value' => 'completed']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'thermal_printer_style'], ['value' => 'with_arabic']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'thermal_printer_footer_english'], ['value' => 'Thank you for shopping! Keep your bill for exchange within 14 days. Terms & Conditions apply.']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'thermal_printer_footer_arabic'], ['value' => 'شكرا للتسوق. يُمكن التبديل خلال 14 يومًا. تطبق الشروط والأحكام.']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'enable_discount_in_print'], ['value' => 'yes']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'enable_total_quantity_in_print'], ['value' => 'yes']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'enable_logo_in_print'], ['value' => 'yes']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'sale_type'], ['value' => 'version_1']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'sale_type'], ['value' => 'pos']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'default_product_type'], ['value' => 'service']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'default_purchase_branch_id'], ['value' => json_encode([1])]);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'print_item_label'], ['value' => 'product']);
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'enable_barcode_in_print'], ['value' => 'yes']);
        $saleVisibleColumns = [
            'created_at' => false,
            'reference_no' => false,
            'branch_id' => false,
            'created_by' => true,
            'customer' => true,
            'payment_method_name' => true,
            'gross_amount' => false,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => false,
            'freight' => false,
            'grand_total' => true,
            'paid' => true,
            'balance' => true,
            'status' => false,
        ];
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'sale_visible_column'], ['value' => json_encode($saleVisibleColumns)]);

        $saleReturnVisibleColumns = [
            'reference_no' => true,
            'branch_id' => false,
            'customer' => true,
            'gross_amount' => true,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => true,
            'grand_total' => true,
            'paid' => false,
            'balance' => false,
            'status' => false,
        ];
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'sale_return_visible_column'], ['value' => json_encode($saleReturnVisibleColumns)]);

        $purchaseVisibleColumns = [
            'branch_id' => false,
            'vendor' => true,
            'gross_amount' => false,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => false,
            'freight' => false,
            'grand_total' => true,
            'paid' => true,
            'balance' => true,
            'status' => false,
        ];
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'purchase_visible_column'], ['value' => json_encode($purchaseVisibleColumns)]);

        $inventoryVisibleColumns = [
            'branch' => true,
            'department' => true,
            'main_category' => true,
            'sub_category' => true,
            'unit' => true,
            'brand' => true,
            'size' => true,
            'code' => true,
            'product_name' => true,
            'quantity' => true,
            'cost' => true,
            'total' => true,
            'barcode' => true,
            'batch' => true,
        ];
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'inventory_visible_column'], ['value' => json_encode($inventoryVisibleColumns)]);

        $this->barcode();

    }

    public function barcode()
    {
        $barcode = config('barcode_default_configuration');
        Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'barcode_configurations'], ['value' => json_encode($barcode)]);
    }
}
