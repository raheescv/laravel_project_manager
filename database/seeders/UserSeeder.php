<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create(['name' => 'Admin', 'email' => 'admin@astra.com', 'password' => Hash::make('asdasd')]);
        User::factory()->create(['name' => 'Rahees', 'email' => 'rahees@astra.com', 'password' => Hash::make('asdasd')]);
    }
}
