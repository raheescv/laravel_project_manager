<?php

use App\Livewire\Settings\Branch\Page;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Settings → Branch runs on the Premium (.brx) form. These keep the redesign
 * honest: the modal still renders in both states, and the new "Exclude from
 * Showcase" switch actually reaches the branch row when it is saved.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    // `permissions` carries a tenant_id, so the row has to be built with one
    // rather than through Spatie's findOrCreate (see PermissionSeeder).
    foreach (['branch.create', 'branch.edit'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    $this->actingAs($this->world->user);
});

it('renders the form for a new branch', function (): void {
    Livewire::test(Page::class)
        ->assertOk()
        ->assertSee('Add New Branch')
        ->assertSee('Exclude from Showcase')
        ->assertSet('branches.exclude_from_showcase', false);
});

it('reflects the flag when an excluded branch is opened', function (): void {
    $this->world->branch->update(['exclude_from_showcase' => true]);

    Livewire::test(Page::class, ['table_id' => $this->world->branch->id])
        ->assertOk()
        ->assertSee('Edit Branch')
        ->assertSet('branches.exclude_from_showcase', true);
});

it('saves the exclusion from the form', function (): void {
    Livewire::test(Page::class, ['table_id' => $this->world->branch->id])
        ->set('branches.exclude_from_showcase', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->world->branch->fresh()->exclude_from_showcase)->toBeTrue();
});

it('saves the exclusion on a branch created from the form', function (): void {
    Livewire::test(Page::class)
        ->set('branches.code', 'WH')
        ->set('branches.name', 'Central Warehouse')
        ->set('branches.exclude_from_showcase', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(\App\Models\Branch::where('code', 'WH')->first()?->exclude_from_showcase)->toBeTrue();
});
