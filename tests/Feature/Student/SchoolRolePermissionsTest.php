<?php

use App\Livewire\Settings\Role\Permissions;
use App\Models\Configuration;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * A School tenant must be able to GRANT the school abilities, reports included.
 *
 * The role screen lists only what the active system declares in
 * config/modules.php, and report abilities are declared one by one — so a report
 * added to config/permissions.php alone is created but never grantable, which is
 * exactly the trap this pins.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'role.permissions', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);

    // Every ability in config/permissions.php, for this tenant.
    foreach (config('permissions') as $group => $actions) {
        foreach ($actions as $action) {
            Permission::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => "{$group}.{$action}", 'guard_name' => 'web']);
        }
    }
    $this->role = Role::create(['tenant_id' => $this->world->tenant->id, 'name' => 'School Admin '.uniqid(), 'guard_name' => 'web']);
});

/** The ability names a role editor can tick right now. */
function grantable($roleId): array
{
    return collect(Livewire::test(Permissions::class, ['role_id' => $roleId])->get('permissions'))
        ->flatMap(fn (array $actions, string $module) => collect($actions)->map(fn ($action) => "{$module}.{$action}")->values())
        ->all();
}

it('offers every school ability, including the two reports, to a School tenant', function (): void {
    StudentWorld::enableSchool($this->world);

    expect(grantable($this->role->id))->toContain(
        'student.view',
        'student card.assign',
        'student topup.create',
        'student topup.refund',
        'report.student wallet',
        'report.student recharge',
    );
});

it('offers none of them to a POS tenant', function (): void {
    Configuration::updateOrCreate(
        ['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'],
        ['value' => 'POS Module'],
    );

    $grantable = grantable($this->role->id);

    expect($grantable)->not->toContain('student.view')
        ->and($grantable)->not->toContain('report.student wallet')
        ->and($grantable)->toContain('sale.create');
});

it('creates the report abilities when permissions are seeded', function (): void {
    Permission::query()->delete();

    (new PermissionSeeder())->run();

    // The seeder writes tenant 1 (see PermissionSeeder); that is the tenant a
    // fresh install rolls out to.
    expect(Permission::withoutGlobalScopes()->where('tenant_id', 1)->pluck('name'))
        ->toContain('report.student wallet', 'report.student recharge', 'student topup.create');
});
