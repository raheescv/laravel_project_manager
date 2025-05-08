<?php

namespace Database\Seeders;

use App\Models\ServicePackage;
use Illuminate\Database\Seeder;

class ServicePackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => '4 Services Package',
                'description' => 'Any 4 services for 400 Riyals',
                'service_count' => 4,
                'amount' => 400,
                'is_active' => true,
            ],
            [
                'name' => '3 Services Package',
                'description' => 'Any 3 services for 300 Riyals',
                'service_count' => 3,
                'amount' => 300,
                'is_active' => true,
            ],
            [
                'name' => '2 Services Package',
                'description' => 'Any 2 services for 200 Riyals',
                'service_count' => 2,
                'amount' => 200,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            ServicePackage::firstOrCreate(['name' => $package['name']], $package);
        }
    }
}
