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
        $data = [
            'name' => 'Default Tenant',
            'code' => 'DEFAULT',
            'subdomain' => explode('.', $domain)[0],
            'domain' => $domain,
            'is_active' => true,
        ];
        Tenant::firstOrCreate($data);
    }
}
