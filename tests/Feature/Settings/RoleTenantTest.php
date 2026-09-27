<?php

use App\Actions\Settings\Role\CreateAction;
use App\Actions\Settings\Role\UpdateAction;
use App\Livewire\Settings\Role\Table;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\TenantService;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Permissions are one shared catalogue; roles belong to a tenant.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->other = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($this->world->tenant);
    $this->actingAs($this->world->user);
});

it('creates a role inside the current tenant', function (): void {
    $response = (new CreateAction())->execute(['name' => 'Cashier Lead']);

    expect($response['success'])->toBeTrue($response['message'])
        ->and($response['data']->tenant_id)->toBe($this->world->tenant->id);
});

it('lists and edits only the current tenant\'s roles', function (): void {
    $own = Role::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Own Role '.uniqid(), 'guard_name' => 'web']);
    $foreign = Role::create(['tenant_id' => $this->other->id, 'name' => 'Foreign Role '.uniqid(), 'guard_name' => 'web']);

    expect(Role::forCurrentTenant()->pluck('id'))->toContain($own->id)->not->toContain($foreign->id)
        ->and((new UpdateAction())->execute(['name' => 'Hijacked'], $foreign->id)['success'])->toBeFalse();

    Livewire::test(Table::class)->assertSee($own->name)->assertDontSee($foreign->name);
});

it('lets a role in any tenant hold the one shared permission row', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'sale.view', 'guard_name' => 'web']);
    $foreign = Role::create(['tenant_id' => $this->other->id, 'name' => 'Admin', 'guard_name' => 'web']);
    $foreign->givePermissionTo($permission);

    expect(Permission::where('name', 'sale.view')->count())->toBe(1)
        ->and($foreign->fresh()->hasPermissionTo('sale.view'))->toBeTrue();
});
