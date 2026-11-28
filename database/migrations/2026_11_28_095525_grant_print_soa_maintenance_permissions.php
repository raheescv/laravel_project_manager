<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The last old-system property permissions without an equivalent: per-type
 * SOA statement / SOA utilities / receipt print / extended residential lease,
 * and the maintenance cancel, delete item and technician edit actions.
 *
 * Every role that can do the action today keeps it: the new permission goes
 * to each role holding the permission that gated the action until now.
 */
return new class() extends Migration
{
    /**
     * New permission => the permission(s) that gated the action until now.
     * Order matters: the SOA grants read 'rent out.print' before it is widened
     * below. An empty list means the capability is new and stays off.
     *
     * @var array<string, list<string>>
     */
    private const GRANTS = [
        'rent out.soa statement' => ['rent out.print'],
        'rent out.soa utilities' => ['rent out.print'],
        'rent out lease.soa statement' => ['rent out.print'],
        'rent out lease.soa utilities' => ['rent out.print'],

        // Receipts and vouchers were not gated, so whoever reaches the payment
        // or services tab keeps printing them.
        'rent out.print' => ['rent out.payment', 'rent out service.view'],
        'rent out lease.print' => ['rent out.print', 'rent out lease.payment', 'rent out service.view'],

        'rent out.residential lease' => ['rent out booking.residential lease'],
        'rent out lease.residential lease' => ['rent out lease booking.residential lease'],

        'maintenance.cancel' => [],
        'maintenance.delete item' => ['maintenance.edit'],
        'maintenance.technician edit' => ['maintenance.view'],
    ];

    /** Permissions that existed before this migration and survive a rollback. */
    private const EXISTING = ['rent out.print'];

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
        Permission::whereIn('name', array_diff(array_keys(self::GRANTS), self::EXISTING))->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
