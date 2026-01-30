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
        User::factory()->create(['tenant_id' => 1, 'name' => 'System', 'email' => 'system@astra.com', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd'), 'is_locked' => 1, 'is_super_admin' => 0]);
        User::factory()->create(['tenant_id' => 1, 'name' => 'Admin', 'email' => 'admin@astra.com', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd'), 'is_locked' => 1, 'is_super_admin' => 1]);
        User::factory()->create(['tenant_id' => 1, 'name' => 'Rahees', 'email' => 'rahees@astra.com', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd'), 'is_super_admin' => 1]);
        User::factory()->create(['tenant_id' => 1, 'name' => 'Employee', 'email' => 'employee@astra.com', 'type' => 'employee', 'mobile' => '+919633155669', 'password' => Hash::make('asdasd'), 'is_super_admin' => 0]);
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
