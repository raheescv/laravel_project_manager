<?php

use App\Exports\InventoryExport;
use App\Livewire\Inventory\ColumnVisibility;
use App\Livewire\Inventory\Table;
use App\Models\Configuration;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Variants are told apart by size and colour, so the inventory list, its column
 * picker and its Excel export carry a Colour column next to Size.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    $this->world->product->update(['size' => '42', 'color' => '#ff0000']);
});

it('shows each item\'s colour by name, even when the product form saved a hex', function (): void {
    Livewire::test(Table::class)
        ->assertSee('Colour')
        ->assertSee('Red')
        ->assertSeeHtml('background: #ff0000');
});

it('adds the Colour column to column settings saved before it existed', function (): void {
    Configuration::create(['key' => 'inventory_visible_column', 'value' => json_encode(['size' => true, 'barcode' => false])]);

    expect(Livewire::test(ColumnVisibility::class)->get('inventory_visible_column'))
        ->toMatchArray(['size' => true, 'color' => true, 'barcode' => false]);
    Livewire::test(Table::class)->assertSee('Colour');
});

it('exports the colour next to the size', function (): void {
    $export = new InventoryExport(['branch_id' => [$this->world->branch->id], 'sortField' => 'inventories.id', 'sortDirection' => 'desc']);
    $headings = $export->headings();
    $row = $export->map($export->query()->first());

    expect($headings[array_search('Size', $headings) + 1])->toBe('Colour')
        ->and($row[array_search('Colour', $headings)])->toBe('Red');
});
