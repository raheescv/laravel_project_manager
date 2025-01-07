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
    }
}
