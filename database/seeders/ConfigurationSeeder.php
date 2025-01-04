<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::firstOrCreate(['key' => 'barcode_type', 'value' => 'product_wise']);
    }
}
