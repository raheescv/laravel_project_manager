<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Everyone may arrange their own home dashboard: `dashboard.customize` goes
 * to every role in every tenant, and straight to users who hold no role, so
 * the Customize button is on for all existing accounts. It stays a normal
 * permission, so a role can still have it switched off in Settings → Roles.
 */
return new class() extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'dashboard.customize', 'guard_name' => 'web']);

        Role::query()->where('guard_name', 'web')->each(fn (Role $role) => $role->givePermissionTo($permission));
        User::withoutGlobalScopes()->whereDoesntHave('roles')->each(fn (User $user) => $user->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'dashboard.customize')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
