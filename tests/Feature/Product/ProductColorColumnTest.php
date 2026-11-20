<?php

use App\Livewire\Product\ColumnVisibility;
use App\Livewire\Product\Table;
use App\Models\Configuration;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The product list has a Color column that can be toggled from Column
 * Visibility, including for tenants whose saved choice predates the column.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->world->product->update(['name' => 'Running Shoe', 'color' => 'Navy']);
});

it('shows the color column on the product list', function (): void {
    Livewire::test(Table::class)
        ->assertSet('product_visible_column.color', true)
        ->assertSee('Color')
        ->assertSee('Navy');
});

it('offers the color toggle even when the saved columns predate it', function (): void {
    Configuration::updateOrCreate(['key' => 'product_visible_column'], ['value' => json_encode(['id' => true, 'size' => false])]);

    Livewire::test(ColumnVisibility::class)
        ->assertSet('product_visible_column.color', true)
        ->assertSet('product_visible_column.size', false)
        ->assertSee('Color');
});

it('hides the color column once toggled off', function (): void {
    Livewire::test(ColumnVisibility::class)->call('toggleColumn', 'color');

    Livewire::test(Table::class)
        ->assertSet('product_visible_column.color', false)
        ->assertDontSee('Navy');
});
