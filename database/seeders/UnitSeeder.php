<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('units')->truncate();
        $data = [];
        $data[] = ['name' => 'Nos', 'code' => 'Nos'];
        $data[] = ['name' => 'Kilogram', 'code' => 'Kg'];
        $data[] = ['name' => 'Gram', 'code' => 'g'];
        $data[] = ['name' => 'Milligram', 'code' => 'Mg'];
        $data[] = ['name' => 'Quintal', 'code' => 'Quintal'];
        $data[] = ['name' => 'Tonne', 'code' => 'Tonne'];
        $data[] = ['name' => 'Liter', 'code' => 'Ltr'];
        $data[] = ['name' => 'Milliliter', 'code' => 'MLtr'];
        $data[] = ['name' => 'Gallon', 'code' => 'Gallon'];
        $data[] = ['name' => 'Cup', 'code' => 'Cup'];
        $data[] = ['name' => 'Teaspoon', 'code' => 'Ts'];
        $data[] = ['name' => 'Drop', 'code' => 'Drop'];
        $data[] = ['name' => 'Square Foot', 'code' => 'Sf'];
        $data[] = ['name' => 'Square Inch ', 'code' => 'SI'];
        $data[] = ['name' => 'Square Kilometer', 'code' => 'SKm'];
        $data[] = ['name' => 'Square Mile', 'code' => 'SM'];
        $data[] = ['name' => 'Square Meter', 'code' => 'SMtr'];
        $data[] = ['name' => 'Square Centimeter', 'code' => 'SCm'];
        $data[] = ['name' => 'Square Millimeter', 'code' => 'SMM'];
        $data[] = ['name' => 'Hectare', 'code' => 'HCTR'];
        $data[] = ['name' => 'Square Yard', 'code' => 'SY'];
        $data[] = ['name' => 'Acre', 'code' => 'acre'];
        $data[] = ['name' => 'Meter', 'code' => 'meter'];
        $data[] = ['name' => 'Centimeter', 'code' => 'Cm'];
        $data[] = ['name' => 'Kilometer', 'code' => 'Km'];
        $data[] = ['name' => 'Foot', 'code' => 'foot'];
        $data[] = ['name' => 'Inch', 'code' => 'inch'];
        DB::table('units')->insert($data);
    }
}
