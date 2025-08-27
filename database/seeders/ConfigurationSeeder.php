<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::firstOrCreate(['key' => 'barcode_type', 'value' => 'product_wise']);
        Configuration::firstOrCreate(['key' => 'contact_no', 'value' => '9633155669']);
        Configuration::firstOrCreate(['key' => 'mobile', 'value' => '9633155669']);
        $payment_methods = [1, 2];
        Configuration::updateOrCreate(['key' => 'payment_methods'], ['value' => json_encode($payment_methods)]);
        Configuration::updateOrCreate(['key' => 'default_status'], ['value' => 'completed']);
        Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => 'with_arabic']);
        Configuration::updateOrCreate(['key' => 'thermal_printer_footer_english'], ['value' => 'Thank you for shopping! Keep your bill for exchange within 14 days. Terms & Conditions apply.']);
        Configuration::updateOrCreate(['key' => 'thermal_printer_footer_arabic'], ['value' => 'شكرا للتسوق. يُمكن التبديل خلال 14 يومًا. تطبق الشروط والأحكام.']);
        Configuration::updateOrCreate(['key' => 'enable_discount_in_print'], ['value' => 'yes']);
        Configuration::updateOrCreate(['key' => 'enable_total_quantity_in_print'], ['value' => 'yes']);
        Configuration::updateOrCreate(['key' => 'enable_logo_in_print'], ['value' => 'yes']);
        Configuration::updateOrCreate(['key' => 'sale_type'], ['value' => 'version_1']);
        Configuration::updateOrCreate(['key' => 'sale_type'], ['value' => 'pos']);
        Configuration::updateOrCreate(['key' => 'default_product_type'], ['value' => 'service']);
        Configuration::updateOrCreate(['key' => 'default_purchase_branch_id'], ['value' => '1']);
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
        Configuration::updateOrCreate(['key' => 'sale_visible_column'], ['value' => json_encode($saleVisibleColumns)]);

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
        Configuration::updateOrCreate(['key' => 'sale_return_visible_column'], ['value' => json_encode($saleReturnVisibleColumns)]);

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
        Configuration::updateOrCreate(['key' => 'purchase_visible_column'], ['value' => json_encode($purchaseVisibleColumns)]);

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
        Configuration::updateOrCreate(['key' => 'inventory_visible_column'], ['value' => json_encode($inventoryVisibleColumns)]);

        $this->barcode();

    }
    public function barcode(){
        $barcode =  config('barcode_default_configuration');
        Configuration::updateOrCreate(['key' => 'barcode_configurations'], ['value' => json_encode($barcode)]);
    }
}
