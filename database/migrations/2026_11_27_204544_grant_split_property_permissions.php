<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Property permissions carried over from the old system split actions that
 * used to ride on a broader permission (extension edit/delete on 'extend',
 * payment edit on 'payment', the rent-out reports on 'view', …). Every role
 * that holds the broader permission today gets the split ones, so nobody
 * loses access on deploy; the restriction only bites once a role has the new
 * permission switched off in Settings → Roles.
 */
return new class() extends Migration
{
    /**
     * New permission => the permission(s) that gated the action until now.
     * A role holding any of them is granted the new one. An empty list means
     * the capability is new and stays off until granted.
     *
     * @var array<string, list<string>>
     */
    private const GRANTS = [
        'rent out.extend edit' => ['rent out.extend'],
        'rent out.extend delete' => ['rent out.extend'],
        'rent out.payment edit' => ['rent out.payment'],
        'rent out.payment history' => ['rent out.payment'],
        'rent out.service edit' => ['rent out.payment'],
        'rent out.customer property report' => ['rent out.view'],
        'rent out.security report' => ['rent out security.view'],
        'rent out.daybook' => ['rent out.view'],

        'rent out lease.extend edit' => ['rent out lease.extend'],
        'rent out lease.extend delete' => ['rent out lease.extend'],
        'rent out lease.payment edit' => ['rent out lease.payment'],
        'rent out lease.payment history' => ['rent out lease.payment'],
        'rent out lease.service edit' => ['rent out lease.payment'],
        'rent out lease.customer property report' => ['rent out.view', 'rent out lease.view'],
        'rent out lease.security report' => ['rent out security.view'],
        'rent out lease.daybook' => ['rent out.view', 'rent out lease.view'],
        'rent out lease.service charge report' => ['rent out lease.view'],

        'rent out security.status change' => ['rent out security.edit'],
        'rent out security.payment edit' => ['rent out security.edit'],
        'rent out security.dashboard overview' => ['rent out security.view'],

        'rent out document.download' => ['rent out document.view', 'rent out.view', 'rent out lease.view', 'property.view'],

        'property dashboard.occupancy rate' => ['property.dashboard overview'],
        'property dashboard.availability rate' => ['property.dashboard overview'],

        'supply request.unit price edit' => ['supply request.create', 'supply request.edit'],
        'supply request.upload attachment after completed' => [],
    ];

    public function up(): void
    {
        foreach (self::GRANTS as $name => $sources) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (! $sources) {
                continue;
            }

            Role::query()
                ->where('guard_name', 'web')
                ->whereHas('permissions', fn ($query) => $query->whereIn('name', $sources))
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::GRANTS))->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
