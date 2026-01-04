<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->insertOrIgnore([
            'name' => 'Admin',
            'code' => 'admin',
            'subdomain' => 'project_manager',
            'domain' => 'project_manager.test',
            'is_active' => 1,
            'description' => 'Admin tenant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('configurations')->where('key', 'theme_settings')->delete();
    }
};
