<?php

use App\Livewire\Property\Group\Page;
use App\Models\PropertyGroup;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * A property group carries a location code, printed as the "Location Code"
 * on the off-plan sale agreement.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);

    $permission = config('permission.models.permission');
    foreach (['create', 'edit'] as $action) {
        $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => "property group.{$action}", 'guard_name' => 'web']));
    }
});

it('saves the location code when creating a property group', function (): void {
    Livewire::test(Page::class)
        ->set('formData.name', 'Marina Tower Group')
        ->set('formData.location_code', 'LSL-MT-01')
        ->call('save');

    expect(PropertyGroup::query()->where('name', 'Marina Tower Group')->value('location_code'))->toBe('LSL-MT-01');
});

it('updates the location code of an existing property group', function (): void {
    $group = PropertyGroup::create(['branch_id' => $this->world->branch->id, 'name' => 'Lusail Group']);

    Livewire::test(Page::class, ['table_id' => $group->id])
        ->set('formData.location_code', 'LSL-02')
        ->call('save');

    expect($group->fresh()->location_code)->toBe('LSL-02');
});
