<?php

use App\Models\Configuration;
use App\Services\NavigationService;
use Tests\Support\PosWorld;

/**
 * Tenant Control is a platform-level screen: every super admin reaches it,
 * whichever system (POS, Tailor, School, …) their tenant runs.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
});

it('keeps Tenant Control in the sidebar for every system', function (?string $system): void {
    if ($system !== null) {
        Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'], ['value' => $system]);
    }

    $this->actingAs($this->world->user);

    $item = collect(NavigationService::getNavigationItems())->firstWhere('id', 'tenants');

    expect($item)->not->toBeNull()
        ->and($item['label'])->toBe('Tenant Control');
})->with([
    'no system chosen' => [null],
    'POS Module' => ['POS Module'],
    'Tailor Module' => ['Tailor Module'],
]);

it('lets only super admins open Tenant Control', function (): void {
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $this->actingAs($this->world->user)->get($this->world->url('/tenants'))->assertForbidden();

    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($this->world->user->fresh())->get($this->world->url('/tenants'))->assertOk()->assertSee('Tenant Control');
});
