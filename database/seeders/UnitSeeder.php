<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Nos', 'code' => 'Nos'],
            ['name' => 'Kilogram', 'code' => 'Kg'],
            ['name' => 'Gram', 'code' => 'g'],
            ['name' => 'Milligram', 'code' => 'Mg'],
            ['name' => 'Quintal', 'code' => 'Quintal'],
            ['name' => 'Tonne', 'code' => 'Tonne'],
            ['name' => 'Liter', 'code' => 'Ltr'],
            ['name' => 'Milliliter', 'code' => 'MLtr'],
            ['name' => 'Gallon', 'code' => 'Gallon'],
            ['name' => 'Cup', 'code' => 'Cup'],
            ['name' => 'Teaspoon', 'code' => 'Ts'],
            ['name' => 'Drop', 'code' => 'Drop'],
            ['name' => 'Square Foot', 'code' => 'Sf'],
            ['name' => 'Square Inch ', 'code' => 'SI'],
            ['name' => 'Square Kilometer', 'code' => 'SKm'],
            ['name' => 'Square Mile', 'code' => 'SM'],
            ['name' => 'Square Meter', 'code' => 'SMtr'],
            ['name' => 'Square Centimeter', 'code' => 'SCm'],
            ['name' => 'Square Millimeter', 'code' => 'SMM'],
            ['name' => 'Hectare', 'code' => 'HCTR'],
            ['name' => 'Square Yard', 'code' => 'SY'],
            ['name' => 'Acre', 'code' => 'acre'],
            ['name' => 'Meter', 'code' => 'meter'],
            ['name' => 'Centimeter', 'code' => 'Cm'],
            ['name' => 'Kilometer', 'code' => 'Km'],
            ['name' => 'Foot', 'code' => 'foot'],
            ['name' => 'Inch', 'code' => 'inch'],
            ['name' => 'Yard', 'code' => 'yard'],
            ['name' => 'Taka', 'code' => 'taka'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['tenant_id' => 1, 'code' => $unit['code']], $unit);
        }
    }
}
