<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_types')->truncate();
        ProductType::factory()->create(['name' => 'Product Type 1']);
        ProductType::factory()->create(['name' => 'Product Type 2']);
        ProductType::factory()->create();
    }
}
