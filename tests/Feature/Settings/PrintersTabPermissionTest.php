<?php

use Tests\Support\PosWorld;

/**
 * Settings → Printers is shown only to POS, student, tailoring and issue users.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);

    $this->grant = function (string $name): void {
        $permission = config('permission.models.permission');
        $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    };

    ($this->grant)('configuration.settings');
});

it('shows the printers tab to users of a printing module', function (string $permission): void {
    ($this->grant)($permission);

    $this->get(route('settings::index'))->assertOk()->assertSee('tabsPrinters', false);
})->with(['sale.create', 'student.view', 'tailoring order.view', 'issue.view']);

it('hides the printers tab from other users', function (string $permission): void {
    ($this->grant)($permission);

    $this->get(route('settings::index'))->assertOk()->assertDontSee('tabsPrinters', false);
})->with(['sale.view', 'configuration.barcode']);
