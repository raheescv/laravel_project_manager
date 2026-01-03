<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::firstOrCreate([
            'name' => 'Default Tenant',
            'code' => 'DEFAULT',
            'subdomain' => 'default',
            'is_active' => true,
        ]);
    }
}
