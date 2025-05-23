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
        $saleVisibleColumns = [
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

        $barcode = [
           'product_name' => [
               'font_size' => '12',
               'no_of_letters' => '26',
               'up' => '2',
               'down' => '123',
               'left' => '0',
               'right' => '0',
               'align' => 'left',
               'top' => '4',
               'bottom' => '0',
           ],
           'product_arabic_name' => [
               'font_size' => '12',
               'no_of_letters' => '200',
               'up' => '123',
               'down' => '23',
               'left' => '0',
               'right' => '0',
               'align' => 'right',
               'top' => '-2',
               'bottom' => '0',
           ],
           'mrp' => [
               'font_size' => '18',
               'up' => '123',
               'down' => '123',
               'left' => '0',
               'right' => '0',
               'align' => 'left',
               'top' => '-15',
               'bottom' => '0',
           ],
           'mrp_arabic' => [
               'font_size' => '18',
               'up' => '123',
               'down' => '123',
               'left' => '0',
               'right' => '0',
               'align' => 'right',
               'top' => '-22',
               'bottom' => '0',
           ],
           'barcode' => [
               'font_size' => '12',
               'up' => '23',
               'down' => '123',
               'left' => '0',
               'right' => '0',
               'align' => 'center',
               'top' => '-14',
               'bottom' => '0',
           ],
           'barcode_image' => [
               'font_size' => '0',
               'up' => '123',
               'down' => '123',
               'left' => '0',
               'right' => '0',
               'align' => 'center',
               'top' => '0',
               'bottom' => '0',
           ],
           'width' => '50',
           'height' => '12',
           'sheet_size' => '1112mm 120mm',
        ];
        Configuration::updateOrCreate(['key' => 'barcode_configurations'], ['value' => json_encode($barcode)]);

    }
}
