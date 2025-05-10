<?php

namespace Database\Seeders;

use App\Models\ComboOffer;
use Illuminate\Database\Seeder;

class ComboOfferSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => '4 Services Combo',
                'description' => 'Any 4 services for 400 Riyals',
                'count' => 4,
                'amount' => 400,
                'is_active' => true,
            ],
            [
                'name' => '3 Services Combo',
                'description' => 'Any 3 services for 300 Riyals',
                'count' => 3,
                'amount' => 300,
                'is_active' => true,
            ],
            [
                'name' => '2 Services Combo',
                'description' => 'Any 2 services for 200 Riyals',
                'count' => 2,
                'amount' => 200,
                'is_active' => true,
            ],
        ];

        foreach ($data as $package) {
            ComboOffer::firstOrCreate(['name' => $package['name']], $package);
        }
    }
}
