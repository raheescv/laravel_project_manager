<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        $permissions = Permission::get();

        $role = Role::create(['name' => 'Super Admin']);
        $role->syncPermissions($permissions);

        $role = Role::create(['name' => 'Admin']);
        $role->syncPermissions($permissions);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
