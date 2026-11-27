<?php

use App\Livewire\Product\OptionGroups;
use App\Livewire\Product\Options;
use App\Models\OptionGroup;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Inventory → Option Groups (build "Flavour", "Drink base"… once) and the
 * product page's Options tab (which groups the till asks, in order).
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    foreach (['option group.view', 'option group.create', 'option group.edit', 'option group.delete', 'product.edit'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('creates a group with its rule and options, and edits it', function (): void {
    Livewire::test(OptionGroups::class)
        ->call('newGroup')
        ->set('form.name', 'Flavour')
        ->call('applyPreset', 1, 1)
        ->set('form.items.0.name', 'Lemongrass')
        ->set('form.items.1.name', 'Mango Passion')
        ->call('addItem')
        ->set('form.items.2.name', 'Strawberry')
        ->set('form.items.2.price', '2')
        ->call('save')
        ->assertDispatched('success')
        ->assertSee('Pick exactly 1');

    $group = OptionGroup::with('items')->where('name', 'Flavour')->sole();
    expect($group->items->pluck('name')->all())->toBe(['Lemongrass', 'Mango Passion', 'Strawberry'])
        ->and($group->items->last()->price)->toBe(2.0)
        ->and([$group->min_select, $group->max_select])->toBe([1, 1]);

    Livewire::test(OptionGroups::class)
        ->call('edit', $group->id)
        ->call('moveItem', 2, -1)
        ->call('removeItem', 0)
        ->call('openStock', 0)
        ->call('pickStock', $this->world->product->id)
        ->call('applyPreset', 0, null)
        ->call('save')
        ->assertDispatched('success');

    $group->refresh()->load('items');
    expect($group->items->pluck('name')->all())->toBe(['Strawberry', 'Mango Passion'])
        ->and($group->items->first()->product_id)->toBe($this->world->product->id)
        ->and($group->max_select)->toBeNull();
});

it('refuses a group without options or with a repeated option', function (): void {
    Livewire::test(OptionGroups::class)
        ->call('newGroup')
        ->set('form.name', 'Empty')
        ->call('save')
        ->assertDispatched('error');

    Livewire::test(OptionGroups::class)
        ->call('newGroup')
        ->set('form.name', 'Twice')
        ->set('form.items.0.name', 'Mint')
        ->set('form.items.1.name', 'mint')
        ->call('save')
        ->assertDispatched('error');

    expect(OptionGroup::count())->toBe(0);
});

it('attaches, orders and removes groups on a product, and will not delete a group in use', function (): void {
    $base = OptionGroup::create(['name' => 'Drink base', 'min_select' => 1, 'max_select' => 1]);
    $base->items()->create(['name' => 'Soda']);
    $flavour = OptionGroup::create(['name' => 'Flavour', 'min_select' => 1, 'max_select' => 1]);
    $flavour->items()->create(['name' => 'Lemongrass']);

    $tab = Livewire::test(Options::class, ['product_id' => $this->world->product->id])
        ->call('attach', $flavour->id)
        ->call('attach', $base->id)
        ->call('move', $base->id, -1);
    expect($this->world->product->optionGroups()->pluck('name')->all())->toBe(['Drink base', 'Flavour']);

    Livewire::test(OptionGroups::class)->call('edit', $base->id)->call('delete')->assertDispatched('error');
    expect(OptionGroup::whereKey($base->id)->exists())->toBeTrue();

    $tab->call('detach', $base->id);
    expect($this->world->product->optionGroups()->pluck('name')->all())->toBe(['Flavour']);
    Livewire::test(OptionGroups::class)->call('edit', $base->id)->call('delete')->assertDispatched('success');
});

it('lets nobody without the permission save a group', function (): void {
    $this->world->user->revokePermissionTo('option group.create');

    Livewire::test(OptionGroups::class)->call('newGroup')->assertForbidden();
});

it('lists the option group permissions under the Product Management module', function (): void {
    expect(config('modules.modules.product_management.permissions'))->toContain('option group');

    foreach (config('modules.systems') as $system => $modules) {
        if (in_array('product_management', $modules, true)) {
            [$groups] = App\Support\ModuleAccess::systemPermissions($system);
            expect($groups)->toContain('option group');
        }
    }
});
