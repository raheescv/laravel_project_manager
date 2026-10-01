<?php

use App\Models\Configuration;
use App\Support\ModuleAccess;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Employee Commission is part of every system except Property Management.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    foreach (['employee.view', 'employee commission.view'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
});

function setCommissionSystem(PosWorld $world, ?string $system): void
{
    $system === null
        ? Configuration::where('tenant_id', $world->tenant->id)->where('key', 'active_module')->delete()
        : Configuration::updateOrCreate(['tenant_id' => $world->tenant->id, 'key' => 'active_module'], ['value' => $system]);
}

it('is off only for the Property Management Module', function (?string $system, bool $expected): void {
    setCommissionSystem($this->world, $system);

    expect(ModuleAccess::enabled(ModuleAccess::EMPLOYEE_COMMISSION))->toBe($expected);
})->with([
    'no system chosen' => [null, true],
    'POS Module' => ['POS Module', true],
    'Tailor Module' => ['Tailor Module', true],
    'Property Management Module' => ['Property Management Module', false],
]);

it('hides the commission page and its sidebar link for a Property tenant', function (): void {
    setCommissionSystem($this->world, 'Property Management Module');

    $this->actingAs($this->world->user)->get($this->world->url('/users/employee/commission'))->assertNotFound();
    $this->actingAs($this->world->user)->get($this->world->url('/users/employee'))
        ->assertOk()
        ->assertDontSee('/users/employee/commission"', false);
});

it('keeps the commission page for a POS tenant', function (): void {
    setCommissionSystem($this->world, 'POS Module');

    $this->actingAs($this->world->user)->get($this->world->url('/users/employee'))
        ->assertOk()
        ->assertSee('/users/employee/commission"', false);
});
