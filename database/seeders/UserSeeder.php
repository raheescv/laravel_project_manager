<?php

namespace Database\Seeders;

use App\Actions\User\BranchAction;
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
        User::factory()->create(['name' => 'Employee', 'email' => 'employee@astra.com', 'type' => 'employee', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd')]);
        $list = User::get();
        $action = new BranchAction();
        foreach ($list as $key => $user) {
            // $user->assignRole('Super Admin');
            $user->assignRole('Admin');
            $user->update(['default_branch_id' => 1]);
            $response = $action->execute($user->id, 1);
        }
    }
}
