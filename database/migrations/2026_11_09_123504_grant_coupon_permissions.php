<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Coupons sit in the Sales menu, so every role that can already edit sales
 * can manage them from day one. They stay normal permissions, so a role can
 * have them switched off in Settings → Roles.
 */
return new class() extends Migration
{
    private const ACTIONS = ['create', 'view', 'edit', 'delete'];

    public function up(): void
    {
        $permissions = collect(self::ACTIONS)->map(fn (string $action) => Permission::firstOrCreate(['name' => "coupon.{$action}", 'guard_name' => 'web']));

        Role::query()
            ->where('guard_name', 'web')
            ->whereHas('permissions', fn ($query) => $query->where('name', 'sale.edit'))
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_map(fn (string $action): string => "coupon.{$action}", self::ACTIONS))->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
