<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create(['name' => 'System', 'email' => 'system@astra.com', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd'), 'is_locked' => 1]);
        User::factory()->create(['name' => 'Admin', 'email' => 'admin@astra.com', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd'), 'is_locked' => 1]);
        User::factory()->create(['name' => 'Rahees', 'email' => 'rahees@astra.com', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd')]);
    }
}
