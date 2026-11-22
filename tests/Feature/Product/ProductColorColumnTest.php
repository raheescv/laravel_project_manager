<?php

use App\Livewire\Product\ColumnVisibility;
use App\Livewire\Product\Table;
use App\Models\Configuration;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The product list shows each product's colour (swatch + name in the product
 * cell), toggled from Column Visibility, including for tenants whose saved choice predates the column.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->world->product->update(['name' => 'Running Shoe', 'color' => 'Navy']);
});

it('shows the colour swatch and name on the product row', function (): void {
    Livewire::test(Table::class)
        ->assertSet('columns.color', true)
        ->assertSeeHtml('class="sw"')
        ->assertSee('Navy');
});

it('offers the color toggle even when the saved columns predate it', function (): void {
    Configuration::updateOrCreate(['key' => 'product_visible_column'], ['value' => json_encode(['id' => true, 'size' => false])]);

    Livewire::test(ColumnVisibility::class)
        ->assertSet('product_visible_column.color', true)
        ->assertSet('product_visible_column.size', false)
        ->assertSee('Color');
});

it('hides the colour once the user turns the column off, only for that user', function (): void {
    Livewire::test(Table::class)
        ->set('columns.color', false)
        ->assertSet('columns.color', false)
        ->assertDontSee('Navy');

    Livewire::test(Table::class)->assertSet('columns.color', false)->assertDontSee('Navy');

    $this->actingAs(\App\Models\User::factory()->create(['tenant_id' => $this->world->user->tenant_id]));
    Livewire::test(Table::class)->assertSet('columns.color', true);
});

it('starts from the tenant-wide column choice and resets back to it', function (): void {
    Configuration::updateOrCreate(['key' => 'product_visible_column'], ['value' => json_encode(['barcode' => false, 'product_name' => false])]);

    Livewire::test(Table::class)
        ->assertSet('columns.barcode', false)
        ->assertSet('columns.cost', true)
        ->set('columns.barcode', true)
        ->call('resetColumns')
        ->assertSet('columns.barcode', false)
        ->assertSee('Visible Columns')
        ->assertSee('SKU Code');
});
