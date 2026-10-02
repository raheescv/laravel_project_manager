<?php

use App\Livewire\Settings\ProductConfiguration;
use App\Models\Configuration;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Product Settings → "Hide Out Of Stock Items In Sale/POS" is a POS option,
 * so only users who can make sales see or change it.
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

it('shows and saves the option for users who can make sales', function (): void {
    ($this->grant)('sale.create');

    Livewire::test(ProductConfiguration::class)
        ->assertSee('Hide Out Of Stock Items In Sale/POS')
        ->set('hide_out_of_stock_sale_items', 'yes')
        ->call('save');

    expect(Configuration::where('key', 'hide_out_of_stock_sale_items')->value('value'))->toBe('yes');
});

it('hides the option and leaves it unchanged for other users', function (): void {
    Configuration::updateOrCreate(['key' => 'hide_out_of_stock_sale_items'], ['value' => 'yes']);

    Livewire::test(ProductConfiguration::class)
        ->assertDontSee('Hide Out Of Stock Items In Sale/POS')
        ->set('hide_out_of_stock_sale_items', 'no')
        ->call('save');

    expect(Configuration::where('key', 'hide_out_of_stock_sale_items')->value('value'))->toBe('yes');
});
