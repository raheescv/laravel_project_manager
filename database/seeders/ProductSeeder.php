<?php

namespace Database\Seeders;

use App\Actions\Product\CreateAction;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->truncate();
        $data = [];
        $faker = Factory::create();
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'name' => $faker->name,
                'department_id' => 'add '.$faker->name,
                'main_category_id' => 'add '.$faker->name,
                'sub_category_id' => 'add '.$faker->name,
                'code' => $faker->hexcolor,
                'barcode' => $faker->hexcolor,
                'unit_id' => 1,
                'cost' => 1,
                'mrp' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ];
        }
        foreach ($data as $value) {
            $response = (new CreateAction)->execute($value, 1);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
        }
    }
}
