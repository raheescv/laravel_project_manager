<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public $data = [];

    public function run(): void
    {
        $permissions = config('permissions');
        foreach ($permissions as $group => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['tenant_id' => 1, 'name' => "{$group}.{$action}"]);
            }
        }
        foreach ($this->data as $key => $value) {
            $this->data[$key]['guard_name'] = 'web';
            $this->data[$key]['created_at'] = $this->data[$key]['updated_at'] = now();
        }
        Permission::insert($this->data);
    }
}
