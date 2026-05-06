<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        $permissions = [
            'asset.dispose',
            'asset.post depreciation',
            'asset.view accounting',
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')->where('name', $permission)->exists();
            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'asset.dispose',
            'asset.post depreciation',
            'asset.view accounting',
        ])->delete();
    }
};
