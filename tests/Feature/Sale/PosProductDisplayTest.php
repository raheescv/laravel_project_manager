<?php

use App\Livewire\Settings\SaleConfiguration;
use App\Models\Configuration;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Sale → "Product Display" decides whether the web POS and the
 * mobile New Sale list every size / colour variant as its own card (the
 * default) or fold them into one family card.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Configuration::where('key', 'pos_product_display')->delete();
});

it('defaults to individual and tells the app not to group variants', function (): void {
    expect(posGroupsVariants())->toBeFalse();

    Sanctum::actingAs($this->world->user);

    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.group_variants', false);
});

it('saves family wise display from Settings → Sale and sends it to the app', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(SaleConfiguration::class)
        ->assertSet('pos_product_display', 'individual')
        ->set('pos_product_display', 'family')
        ->call('save');

    expect(Configuration::where('key', 'pos_product_display')->value('value'))->toBe('family')
        ->and(posGroupsVariants())->toBeTrue();

    Sanctum::actingAs($this->world->user);

    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.group_variants', true);
});

it('stores anything unrecognised as individual', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(SaleConfiguration::class)
        ->set('pos_product_display', 'bogus')
        ->call('save');

    expect(Configuration::where('key', 'pos_product_display')->value('value'))->toBe('individual');
});
