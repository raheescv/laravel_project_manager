<?php

use App\Models\Product;
use Tests\Support\PosWorld;

/**
 * The mobile New Sale grid folds size / colour variants into one card per
 * family (`parent_id ?? id`) and asks for the size and colour, so every catalog
 * row carries its family, size and a colour ready to draw.
 *
 * @see App\Http\Resources\V1\ProductResource
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 5);
    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => '#ff0000']);
    $this->blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();
});

it('gives each catalog row its family, size and drawable colour', function (): void {
    cache()->flush();

    $rows = collect($this->getJson($this->world->url('/api/v1/products?in_stock_only=0&search=Running'))
        ->assertSuccessful()->json('data.data'));

    $head = $rows->firstWhere('id', $this->shoe->id);
    $blue = $rows->firstWhere('id', $this->blue->id);

    expect($head['parent_id'])->toBeNull()
        ->and($head['size'])->toBe('41')
        ->and($head['color_name'])->toBe('Red')
        ->and($head['color_hex'])->toBe('#ff0000')
        ->and($blue['parent_id'])->toBe($this->shoe->id)
        ->and($blue['color_name'])->toBe('Blue')
        ->and($blue['color_hex'])->toBe('#2563eb');
});

it('lists a family once, carrying its sizes, colours, price range and stock', function (): void {
    cache()->flush();
    $this->blue->update(['mrp' => 90]);
    $this->blue->inventories()->create([
        'branch_id' => $this->world->branch->id,
        'quantity' => 3,
        'cost' => 10,
        'batch' => 'B-42',
        'barcode_prefix' => '',
        'barcode_number' => '99004242',
    ]);
    Product::factory()->variantOf($this->shoe, '43', 'Blue')->create();

    $rows = collect($this->getJson($this->world->url('/api/v1/products?group_by_family=1&in_stock_only=1&type=product&search=Running'))
        ->assertSuccessful()->json('data.data'));

    $card = $rows->sole();
    expect($card['id'])->toBe($this->shoe->id)
        ->and($card['family']['count'])->toBe(2)
        ->and($card['family']['sizes'])->toBe(['41', '42'])
        ->and(collect($card['family']['colours'])->pluck('name')->all())->toBe(['Red', 'Blue'])
        ->and((float) $card['family']['max_price'])->toBe(90.0)
        ->and((float) $card['family']['stock_total'])->toBe(8.0);
});

it('lands a size search on that size, still once per family', function (): void {
    cache()->flush();
    $this->blue->inventories()->create([
        'branch_id' => $this->world->branch->id,
        'quantity' => 2,
        'cost' => 10,
        'batch' => 'B-42',
        'barcode_prefix' => '',
        'barcode_number' => '99004243',
    ]);

    $card = collect($this->getJson($this->world->url('/api/v1/products?group_by_family=1&size=42&type=product&search=Running'))
        ->assertSuccessful()->json('data.data'))->sole();

    expect($card['id'])->toBe($this->blue->id)
        ->and($card['family']['sizes'])->toBe(['41', '42']);
});

it('returns every member of a family by family_id', function (): void {
    cache()->flush();

    $ids = collect($this->getJson($this->world->url("/api/v1/products?family_id={$this->shoe->id}&in_stock_only=0"))
        ->assertSuccessful()->json('data.data'))->pluck('id')->sort()->values()->all();

    expect($ids)->toBe([$this->shoe->id, $this->blue->id]);
});

it('tells the app each item\'s design and what the family calls that layer', function (): void {
    cache()->flush();
    Product::whereKey([$this->shoe->id, $this->blue->id])->update(['variant_option' => 'Flavour', 'variant_size_option' => 'Weight']);
    $this->blue->update(['variant_value' => 'Mango']);

    $rows = collect($this->getJson($this->world->url('/api/v1/products?in_stock_only=0&search=Running'))
        ->assertSuccessful()->json('data.data'));

    expect($rows->firstWhere('id', $this->shoe->id)['option_name'])->toBe('Flavour')
        ->and($rows->firstWhere('id', $this->shoe->id)['size_name'])->toBe('Weight')
        ->and($rows->firstWhere('id', $this->shoe->id)['design'])->toBeNull()
        ->and($rows->firstWhere('id', $this->blue->id)['design'])->toBe('Mango');
});
