<?php

use App\Actions\Product\Inventory\GetProductAction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * The inventory search (/inventory/search) opens on sizes small to big.
 * `products.size` is a text column, so the ordering has to read "4" as less
 * than "10" and keep letter sizes and blanks out of the way of the numbers.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 5);
});

/** A stocked product in this tenant with the given size. */
function stockedProductOfSize(PosWorld $world, ?string $size): Product
{
    $product = Product::create([
        'tenant_id' => $world->tenant->id,
        'type' => 'product',
        'name' => 'Sized Product '.Str::random(6),
        'code' => 'S'.Str::upper(Str::random(6)),
        'size' => $size,
        'unit_id' => $world->product->unit_id,
        'department_id' => $world->product->department_id,
        'main_category_id' => $world->product->main_category_id,
        'mrp' => 25,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
    ]);

    DB::table('inventories')->insert([
        'tenant_id' => $world->tenant->id,
        'branch_id' => $world->branch->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'batch' => (string) Str::uuid(),
        'cost' => 10,
        'barcode_prefix' => '',
        'barcode_number' => (string) random_int(10000000, 99999999),
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $product;
}

/** The sizes of the search rows, in the order the action returns them. */
function searchedSizes(string $direction): array
{
    $result = (new GetProductAction())->execute([
        'sortField' => 'products.size',
        'sortDirection' => $direction,
        'show_non_zero' => 0,
        'limit' => 100,
    ]);

    return array_column($result['data'], 'size');
}

it('reads numeric sizes as numbers, then letter sizes, then rows with no size', function (): void {
    // Inserted out of order on purpose; the world's own product has no size.
    foreach (['10', 'S', '36.5', '4', 'M', '36'] as $size) {
        stockedProductOfSize($this->world, $size);
    }

    expect(searchedSizes('asc'))->toBe(['4', '10', '36', '36.5', 'M', 'S', null]);
});

it('keeps letter sizes and blanks after the numbers when sorting big to small', function (): void {
    foreach (['10', 'S', '36.5', '4', 'M', '36'] as $size) {
        stockedProductOfSize($this->world, $size);
    }

    expect(searchedSizes('desc'))->toBe(['36.5', '36', '10', '4', 'S', 'M', null]);
});
