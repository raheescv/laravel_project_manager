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

        // These seeders are safe to run in any environment as they use firstOrCreate
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(AccountSeeder::class);
        $this->call(ConfigurationSeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(CountrySeeder::class);

        // UserSeeder should only run if users table is empty
        if (DB::table('users')->count() === 0) {
            $this->call(UserSeeder::class);
        }

        Artisan::call('optimize');
        Artisan::call('config:cache');

        // Development-only seeders that contain sample data
        if (! app()->isProduction()) {
            $this->call(DepartmentSeeder::class);
            $this->call(ProductSeeder::class);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
