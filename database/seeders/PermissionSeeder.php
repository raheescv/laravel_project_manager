<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public $data = [];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();

        $permissions = config('permissions');
        foreach ($permissions as $group => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$group}.{$action}"]);
            }
        }
        foreach ($this->data as $key => $value) {
            $this->data[$key]['guard_name'] = 'web';
            $this->data[$key]['created_at'] = $this->data[$key]['updated_at'] = now();
        }
        Permission::insert($this->data);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
