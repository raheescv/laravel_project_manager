<?php

use App\Livewire\Inventory\Barcode\CartPage;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The print cart has two inputs: the scanner box matches barcodes, the search box
 * also matches product name and code. Neither may filter on the other's value —
 * the search box once required the (empty) scanner box to equal the barcode.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);

    $this->inventoryBarcode = DB::table('inventories')->where('product_id', $this->world->product->id)->value('barcode');
});

it('finds stocked products by name from the search box', function (): void {
    Livewire::test(CartPage::class)
        ->set('searchQuery', $this->world->product->name)
        ->assertCount('products', 1)
        ->assertSet('products.0.product_id', $this->world->product->id)
        ->assertSet('products.0.item_type', 'inventory');
});

it('finds stocked products by code from the search box', function (): void {
    Livewire::test(CartPage::class)
        ->set('searchQuery', $this->world->product->code)
        ->assertCount('products', 1)
        ->assertSet('products.0.product_id', $this->world->product->id);
});

it('matches a partly typed barcode in the scanner box without listing unrelated units', function (): void {
    DB::table('product_units')->insert([
        'tenant_id' => $this->world->tenant->id,
        'product_id' => $this->world->product->id,
        'sub_unit_id' => $this->world->product->unit_id,
        'conversion_factor' => 12,
        'barcode_prefix' => '',
        'barcode_number' => 'BOX-0001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(CartPage::class)
        ->set('barcodeInput', substr($this->inventoryBarcode, 0, 5))
        ->assertCount('products', 1)
        ->assertSet('products.0.item_type', 'inventory')
        ->set('barcodeInput', 'BOX-0')
        ->assertCount('products', 1)
        ->assertSet('products.0.item_type', 'product_unit');
});
