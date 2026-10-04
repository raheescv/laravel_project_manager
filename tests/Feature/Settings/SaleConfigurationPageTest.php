<?php

use App\Livewire\Settings\SaleConfiguration;
use App\Models\Configuration;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Sale: fields grouped into sections, yes/no settings shown as switches.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
});

it('groups the settings into sections with switches for yes/no options', function (): void {
    Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'enable_tip'], ['value' => 'yes']);
    Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'show_colleague'], ['value' => 'no']);

    Livewire::test(SaleConfiguration::class)
        ->assertSeeInOrder(['Checkout Defaults', 'Checkout Rules', 'Staff &amp; Day Sessions', 'POS Screen', 'Receipt'], false)
        ->assertSeeHtml('id="sc-enable_tip" checked')
        ->assertDontSeeHtml('id="sc-show_colleague" checked');
});

it('saves a switch flipped to off', function (): void {
    Livewire::test(SaleConfiguration::class)
        ->set('enable_tip', 'no')
        ->set('prevent_out_of_stock_sales', 'no')
        ->call('save');

    expect(Configuration::where('key', 'enable_tip')->value('value'))->toBe('no')
        ->and(Configuration::where('key', 'prevent_out_of_stock_sales')->value('value'))->toBe('no');
});

it('shows the barcode on receipts by default, as the receipt does', function (): void {
    Livewire::test(SaleConfiguration::class)->assertSet('enable_barcode_in_print', 'yes');
});
