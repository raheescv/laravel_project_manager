<?php

use App\Livewire\Settings\Role\Permissions;
use App\Support\MobileAppPermissions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\PosWorld;

/**
 * The role editor's "Mobile App" filter lists only the abilities the mobile
 * app checks, and filtering never revokes the abilities it hides.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'name' => 'role.permissions', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);

    foreach (config('permissions') as $group => $actions) {
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "{$group}.{$action}", 'guard_name' => 'web']);
        }
    }
    $this->role = Role::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Cashier '.uniqid(), 'guard_name' => 'web']);
});

/**
 * @return list<string>
 */
function listedAbilities($component): array
{
    return collect($component->get('permissions'))
        ->flatMap(fn (array $actions, string $module) => collect($actions)->map(fn ($action) => "{$module}.{$action}")->values())
        ->all();
}

it('every mobile app permission is seeded from config/permissions.php', function (): void {
    $seeded = collect(config('permissions'))
        ->flatMap(fn (array $actions, string $group) => collect($actions)->map(fn ($action) => "{$group}.{$action}"))
        ->all();

    expect(array_diff(MobileAppPermissions::NAMES, $seeded))->toBeEmpty();
});

it('lists only the mobile app permissions under the Mobile App filter', function (): void {
    $component = Livewire::test(Permissions::class, ['role_id' => $this->role->id]);

    expect(listedAbilities($component))->toContain('account.create', 'sale.create');

    $component->set('platform', 'mobile');
    $listed = listedAbilities($component);

    expect($listed)->toContain('sale.create', 'day session.create', 'sale.mobile change employee', 'sale.queue token')
        ->and($listed)->not->toContain('account.create')
        ->and(array_diff($listed, MobileAppPermissions::NAMES))->toBeEmpty();
});

it('keeps hidden web permissions when saving under the Mobile App filter', function (): void {
    $this->role->givePermissionTo('account.create');

    $saleCreateId = Permission::findByName('sale.create', 'web')->id;

    Livewire::test(Permissions::class, ['role_id' => $this->role->id])
        ->set('platform', 'mobile')
        ->set("selected.{$saleCreateId}", true)
        ->call('save');

    expect($this->role->fresh()->permissions->pluck('name')->all())
        ->toContain('account.create', 'sale.create');
});
