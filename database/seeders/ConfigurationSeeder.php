<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::firstOrCreate(['key' => 'barcode_type', 'value' => 'product_wise']);
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
            'reference_no' => true,
            'branch_id' => false,
            'customer' => true,
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
    }
}
