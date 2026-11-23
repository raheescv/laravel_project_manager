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

it('filters by size and colour separately, leading a variant to its family when grouped', function (): void {
    expect(productListIds(['size' => '42']))->toBe([$this->shoe->id])
        ->and(productListIds(['groupVariants' => false, 'size' => '42']))->toBe([$this->red->id])
        ->and(productListIds(['color' => 'brown']))->toBe([$this->bag->id])
        ->and(productListIds(['groupVariants' => false, 'color' => 'Red']))->toBe([$this->red->id])
        ->and(productListIds(['size' => '41', 'color' => 'Red']))->toBe([$this->shoe->id])
        ->and(productListIds(['groupVariants' => false, 'size' => '41', 'color' => 'Red']))->toBe([]);
});

it('offers every used size and colour once, sizes in natural order', function (): void {
    Product::factory()->create(['unit_id' => $this->shoe->unit_id, 'name' => 'Belt', 'size' => '100', 'color' => ' black ']);

    $table = Livewire::test(Table::class)->instance();

    expect($table->sizeOptions())->toBe(['41', '42', '100'])
        ->and($table->colourOptions())->toBe(['Black' => 'Black', 'Brown' => 'Brown', 'Red' => 'Red']);
});

it('clears the search with the filters and shows a colour chip', function (): void {
    $table = Livewire::test(Table::class)
        ->set('search', 'shoe')
        ->set('color', 'Red')
        ->set('size', '42');

    expect($table->viewData('activeFilters'))->toBe([
        'size' => ['label' => 'Size', 'value' => '42'],
        'color' => ['label' => 'Colour', 'value' => 'Red'],
    ]);

    $table->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('color', '')
        ->assertSet('size', '');
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
