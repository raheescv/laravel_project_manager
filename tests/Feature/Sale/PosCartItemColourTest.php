<?php

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * A cart line shows the variant's colour next to its size, as a name with a
 * swatch; a hex picked in the product form reads as its colour name.
 *
 * @see App\Actions\Sale\Pos\AddItemAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    Configuration::updateOrCreate(['key' => 'default_quantity'], ['value' => '1']);

    $this->inventoryId = DB::table('inventories')->where('product_id', $this->world->product->id)->value('id');
    $this->addItem = fn () => $this->postJson($this->world->url('pos/add-item'), [
        'inventory_id' => $this->inventoryId,
        'employee_id' => $this->world->user->id,
    ])->assertOk()->json();
});

it('gives a cart line its colour name and swatch', function (): void {
    $this->world->product->update(['size' => '40.5', 'color' => 'Black']);

    $item = ($this->addItem)();

    expect($item['size'])->toBe('40.5')
        ->and($item['color'])->toBe('Black')
        ->and($item['color_hex'])->toBe('#1f2328');
});

it('names a picked hex colour and leaves a product without colour blank', function (): void {
    $this->world->product->update(['color' => '#ff0000']);
    $item = ($this->addItem)();
    expect($item['color'])->toBe('Red')->and($item['color_hex'])->toBe('#ff0000');

    $this->world->product->update(['color' => null]);
    $item = ($this->addItem)();
    expect($item['color'])->toBeNull()->and($item['color_hex'])->toBeNull();
});
