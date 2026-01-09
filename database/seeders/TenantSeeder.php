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
        $appUrl = config('app.url');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?? 'project_manager.test';

        Tenant::firstOrCreate([
            'name' => 'Default Tenant',
            'code' => 'DEFAULT',
            'subdomain' => 'project_manager',
            'domain' => $domain,
            'is_active' => true,
        ]);
    }
}
