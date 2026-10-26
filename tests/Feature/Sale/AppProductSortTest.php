<?php

use App\Livewire\Settings\SaleConfiguration;
use App\Models\Configuration;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Sale → "POS Product Order" decides how the web POS and mobile New Sale list
 * products: by name or priority, ascending or descending. Stored in the shared
 * Sale Configuration, editable from the web and from the app.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Configuration::whereIn('key', ['app_product_sort_by', 'app_product_sort_direction'])->delete();
});

it('defaults to priority ascending and sends it to the app', function (): void {
    Sanctum::actingAs($this->world->user);

    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.product_sort', ['by' => 'priority', 'direction' => 'asc']);
});

it('saves the order from Settings → Sale', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(SaleConfiguration::class)
        ->assertSet('app_product_sort_by', 'priority')
        ->assertSet('app_product_sort_direction', 'asc')
        ->set('app_product_sort_by', 'name')
        ->set('app_product_sort_direction', 'desc')
        ->call('save');

    expect(appProductSort())->toBe(['by' => 'name', 'direction' => 'desc']);
});

it('lets the app change the order with the settings permission', function (): void {
    $this->world->user->givePermissionTo(config('permission.models.permission')::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));
    Sanctum::actingAs($this->world->user);

    $this->putJson($this->world->url('/api/v1/settings/sale/product-sort'), ['by' => 'priority', 'direction' => 'desc'])
        ->assertSuccessful()
        ->assertJsonPath('data.product_sort', ['by' => 'priority', 'direction' => 'desc']);

    expect(Configuration::where('key', 'app_product_sort_direction')->value('value'))->toBe('desc');
});

it('rejects an unknown order', function (): void {
    $this->world->user->givePermissionTo(config('permission.models.permission')::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));
    Sanctum::actingAs($this->world->user);

    $this->putJson($this->world->url('/api/v1/settings/sale/product-sort'), ['by' => 'price', 'direction' => 'up'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['by', 'direction']);
});

it('blocks an account without the settings permission', function (): void {
    Sanctum::actingAs($this->world->user);

    $this->putJson($this->world->url('/api/v1/settings/sale/product-sort'), ['by' => 'name', 'direction' => 'asc'])
        ->assertForbidden();

    expect(Configuration::where('key', 'app_product_sort_by')->exists())->toBeFalse();
});
