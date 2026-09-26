<?php

namespace Database\Seeders;

use App\Actions\User\BranchAction;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /** The tenant these rows belong to; the Tenant Control provisioner points it elsewhere. */
    public int $tenantId = 1;

    /**
     * Super admin reaches Tenant Control for EVERY tenant, so only the home
     * install hands it out; the provisioner turns it off for other tenants.
     */
    public bool $withSuperAdmins = true;

    public function run(): void
    {
        $users = [
            ['name' => 'System', 'email' => 'system@astra.com', 'is_locked' => 1, 'is_super_admin' => 0],
            ['name' => 'Admin', 'email' => 'admin@astra.com', 'is_locked' => 1, 'is_super_admin' => 1],
            ['name' => 'Rahees', 'email' => 'rahees@astra.com', 'is_super_admin' => 1],
            ['name' => 'Employee', 'email' => 'employee@astra.com', 'type' => 'employee', 'is_super_admin' => 0],
        ];

        $role = Role::firstOrCreate(['tenant_id' => $this->tenantId, 'name' => 'Admin', 'guard_name' => 'web']);
        $branchId = Branch::withoutGlobalScopes()->where('tenant_id', $this->tenantId)->orderBy('id')->value('id');
        $action = new BranchAction();

        foreach ($users as $attributes) {
            $user = User::withoutGlobalScopes()->where('tenant_id', $this->tenantId)->where('email', $attributes['email'])->first();
            if (! $user) {
                $attributes['is_super_admin'] = $this->withSuperAdmins ? $attributes['is_super_admin'] : 0;
                $user = User::factory()->create([
                    ...$attributes,
                    'tenant_id' => $this->tenantId,
                    'mobile' => '+919633155669',
                    'password' => Hash::make('asdasd'),
                ]);
            }

            $user->assignRole($role);
            if ($branchId) {
                $action->execute($user->id, [$branchId], BranchAction::MODE_APPEND, $user->default_branch_id ?: $branchId);
            }
        }
    }
}
