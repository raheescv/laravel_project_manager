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
        $data[] = ['tenant_id' => 1, 'name' => 'Nos', 'code' => 'Nos'];
        $data[] = ['tenant_id' => 1, 'name' => 'Kilogram', 'code' => 'Kg'];
        $data[] = ['tenant_id' => 1, 'name' => 'Gram', 'code' => 'g'];
        $data[] = ['tenant_id' => 1, 'name' => 'Milligram', 'code' => 'Mg'];
        $data[] = ['tenant_id' => 1, 'name' => 'Quintal', 'code' => 'Quintal'];
        $data[] = ['tenant_id' => 1, 'name' => 'Tonne', 'code' => 'Tonne'];
        $data[] = ['tenant_id' => 1, 'name' => 'Liter', 'code' => 'Ltr'];
        $data[] = ['tenant_id' => 1, 'name' => 'Milliliter', 'code' => 'MLtr'];
        $data[] = ['tenant_id' => 1, 'name' => 'Gallon', 'code' => 'Gallon'];
        $data[] = ['tenant_id' => 1, 'name' => 'Cup', 'code' => 'Cup'];
        $data[] = ['tenant_id' => 1, 'name' => 'Teaspoon', 'code' => 'Ts'];
        $data[] = ['tenant_id' => 1, 'name' => 'Drop', 'code' => 'Drop'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Foot', 'code' => 'Sf'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Inch ', 'code' => 'SI'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Kilometer', 'code' => 'SKm'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Mile', 'code' => 'SM'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Meter', 'code' => 'SMtr'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Centimeter', 'code' => 'SCm'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Millimeter', 'code' => 'SMM'];
        $data[] = ['tenant_id' => 1, 'name' => 'Hectare', 'code' => 'HCTR'];
        $data[] = ['tenant_id' => 1, 'name' => 'Square Yard', 'code' => 'SY'];
        $data[] = ['tenant_id' => 1, 'name' => 'Acre', 'code' => 'acre'];
        $data[] = ['tenant_id' => 1, 'name' => 'Meter', 'code' => 'meter'];
        $data[] = ['tenant_id' => 1, 'name' => 'Centimeter', 'code' => 'Cm'];
        $data[] = ['tenant_id' => 1, 'name' => 'Kilometer', 'code' => 'Km'];
        $data[] = ['tenant_id' => 1, 'name' => 'Foot', 'code' => 'foot'];
        $data[] = ['tenant_id' => 1, 'name' => 'Inch', 'code' => 'inch'];
        DB::table('units')->insert($data);
    }
}
