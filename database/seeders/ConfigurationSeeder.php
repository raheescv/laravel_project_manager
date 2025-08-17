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

        $barcode = [
            'width' => 50,
            'height' => 30,
            'product_name' => [
                'font_size' => 9,
                'align' => 'left',
                'visible' => true,
                'char_limit' => 40,
            ],
            'product_name_arabic' => [
                'font_size' => 8,
                'align' => 'right',
                'visible' => true,
                'char_limit' => 32,
            ],
            'barcode' => [
                'font_size' => 12,
                'align' => 'center',
                'visible' => true,
                'show_value' => true,
                'scale' => 4,
            ],
            'price' => [
                'font_size' => 16,
                'align' => 'left',
                'visible' => true,
            ],
            'price_arabic' => [
                'font_size' => 14,
                'align' => 'right',
                'visible' => true,
            ],
            'elements' => [
                'product_name' => [
                    'top' => 1,
                    'left' => 2,
                    'width' => 180,
                    'height' => 15,
                ],
                'product_name_arabic' => [
                    'top' => 16,
                    'left' => 2,
                    'width' => 180,
                    'height' => 15,
                ],
                'barcode' => [
                    'top' => 32,
                    'left' => 2,
                    'width' => 180,
                    'height' => 42,
                ],
                'price' => [
                    'top' => 78,
                    'left' => 2,
                    'width' => 85,
                    'height' => 18,
                ],
                'price_arabic' => [
                    'top' => 78,
                    'left' => 95,
                    'width' => 85,
                    'height' => 18,
                ],
            ],
        ];
        Configuration::updateOrCreate(['key' => 'barcode_configurations'], ['value' => json_encode($barcode)]);

    }
}
