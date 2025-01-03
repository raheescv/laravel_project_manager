<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);

        $this->call(UnitSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(UserSeeder::class);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
