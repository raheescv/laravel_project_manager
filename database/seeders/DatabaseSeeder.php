<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);

        $this->call(AccountSeeder::class);
        $this->call(ConfigurationSeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CountrySeeder::class);

        Artisan::call('optimize');
        Artisan::call('config:cache');

        if (! app()->isProduction()) {
            $this->call(DepartmentSeeder::class);
            $this->call(ProductSeeder::class);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
