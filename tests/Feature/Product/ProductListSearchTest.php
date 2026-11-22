<?php

use App\Livewire\Product\Table;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The product list search: every word must match, numbers match an exact
 * price, variants lead to their family, and sorting is limited to real columns.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black', 'mrp' => 250]);
    $this->red = Product::factory()->variantOf($this->shoe, '42', 'Red')->create();
    $this->bag = Product::factory()->create(['unit_id' => $this->shoe->unit_id, 'name' => 'Leather Bag', 'color' => 'Brown', 'mrp' => 1250]);
});

function productListIds(array $state): array
{
    $table = Livewire::test(Table::class);
    foreach ($state as $key => $value) {
        $table->set($key, $value);
    }

    return $table->viewData('data')->pluck('id')->sort()->values()->all();
}

it('needs every word to match somewhere on the product', function (): void {
    expect(productListIds(['search' => 'shoe black']))->toBe([$this->shoe->id])
        ->and(productListIds(['search' => 'shoe brown']))->toBe([])
        ->and(productListIds(['search' => '  leather   bag ']))->toBe([$this->bag->id]);
});

it('matches a number against the exact price, not any price containing it', function (): void {
    expect(productListIds(['search' => '250']))->toBe([$this->shoe->id]);
});

it('finds the family through a variant when grouped and the variant itself when flat', function (): void {
    expect(productListIds(['search' => 'red']))->toBe([$this->shoe->id])
        ->and(productListIds(['groupVariants' => false, 'search' => 'red']))->toBe([$this->red->id]);
});

it('filters by the exact SKU code, leading a variant code to its family when grouped', function (): void {
    $this->shoe->update(['code' => 'SHOE-41']);
    $this->red->update(['code' => 'SHOE-42-RED']);
    $this->bag->update(['code' => 'BAG-001']);

    expect(productListIds(['sku' => ' bag-001 ']))->toBe([$this->bag->id])
        ->and(productListIds(['sku' => 'BAG-0']))->toBe([])
        ->and(productListIds(['sku' => 'SHOE-42-RED']))->toBe([$this->shoe->id])
        ->and(productListIds(['groupVariants' => false, 'sku' => 'SHOE-42-RED']))->toBe([$this->red->id]);
});

it('lists applied filters as chips and clears them one at a time or all at once', function (): void {
    $this->bag->update(['code' => 'BAG-001']);

    $table = Livewire::test(Table::class)
        ->set('sku', 'BAG-001')
        ->set('is_selling', true)
        ->set('variant', 'main');

    expect($table->viewData('activeFilters'))->toBe([
        'sku' => ['label' => 'SKU', 'value' => 'BAG-001'],
        'variant' => ['label' => 'Variant', 'value' => 'Main products only'],
        'is_selling' => ['label' => 'Sales', 'value' => 'Currently selling'],
    ]);

    $table->call('clearFilter', 'sku')
        ->assertSet('sku', '')
        ->assertDispatched('product-filters-cleared', keys: ['sku'])
        ->call('clearFilter', 'search')
        ->call('clearFilters')
        ->assertSet('variant', '')
        ->assertSet('is_selling', '');

    expect($table->viewData('activeFilters'))->toBe([]);
});

it('shows 50 rows by default and remembers the compact density', function (): void {
    Livewire::test(Table::class)
        ->assertSet('limit', 50)
        ->set('compact', true)
        ->assertSeeHtml('ptable is-compact');

    expect(session('product.table.compact'))->toBeTrue();
});

it('ignores a sort field that is not a list column', function (): void {
    Livewire::test(Table::class)
        ->set('sortField', 'password')
        ->assertOk();
});
