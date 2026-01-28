<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // DB::table('products')->truncate();

        $faker = Factory::create();
        $data = [];

        $departmentIds = DB::table('departments')->pluck('id')->toArray();
        $mainCategoryIds = DB::table('categories')->pluck('id')->toArray();

        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'tenant_id' => 1,
                'type' => 'product',
                'name' => ucfirst($faker->unique()->word),
                'department_id' => ! empty($departmentIds) ? $faker->randomElement($departmentIds) : null,
                'main_category_id' => ! empty($mainCategoryIds) ? $faker->randomElement($mainCategoryIds) : null,
                'code' => strtoupper($faker->bothify('??###')),
                'barcode' => strtoupper($faker->bothify('##??##')),
                'unit_id' => 1,
                'cost' => rand(99, 999),
                'mrp' => rand(999, 9999),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('products')->insert($data);

        $this->command->info('✅ Seeded 100 unique products successfully.');
    }
}
