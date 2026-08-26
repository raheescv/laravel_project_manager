<?php

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * GET /api/v1/products is the catalog the POS renders from — the New Sale grid,
 * search-as-you-type, barcode scans, and the offline snapshot that walks every
 * page. It is public (no login), so the tenant host is the only context it has.
 *
 * These cover the contract the app depends on: a scan resolves to one product,
 * stock reflects the branch that was asked for, and the payload stays the
 * lightweight card shape rather than the full detail record.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 12);
});

/** Another product in this tenant, with no stock anywhere unless given some. */
function makeProduct(PosWorld $world, array $attributes = []): Product
{
    return Product::create(array_merge([
        'tenant_id' => $world->tenant->id,
        'type' => 'product',
        'name' => 'Extra Product '.Str::random(6),
        'code' => 'X'.Str::upper(Str::random(6)),
        'unit_id' => $world->product->unit_id,
        'department_id' => $world->product->department_id,
        'main_category_id' => $world->product->main_category_id,
        'mrp' => 25,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
    ], $attributes));
}

function stockAt(PosWorld $world, Product $product, int $branchId, float $quantity): void
{
    DB::table('inventories')->insert([
        'tenant_id' => $world->tenant->id,
        'branch_id' => $branchId,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'batch' => (string) Str::uuid(),
        'cost' => 10,
        'barcode_prefix' => '',
        'barcode_number' => (string) random_int(10000000, 99999999),
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/** The list rows for [query], with the count cache cleared so each test is cold. */
function listProducts(PosWorld $world, array $query = []): array
{
    cache()->flush();

    $response = test()->getJson($world->url('/api/v1/products?'.http_build_query($query + ['in_stock_only' => 0])));
    $response->assertSuccessful();

    return $response->json('data.data');
}

it('resolves a barcode to that product alone', function (): void {
    // A scan goes through the list endpoint. Before the barcode filter was
    // applied, it fell through to "the whole catalog" and the app added
    // whichever product happened to sort first to the cart.
    // `products.barcode` is generated from prefix + number, so seed the number.
    $this->world->product->update(['barcode_number' => '5012345']);
    makeProduct($this->world, ['barcode_number' => '9998888']);

    $rows = listProducts($this->world, ['barcode' => '5012345']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['id'])->toBe($this->world->product->id);
});

it('resolves a product id to that product alone', function (): void {
    makeProduct($this->world);

    $rows = listProducts($this->world, ['product_id' => $this->world->product->id]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['id'])->toBe($this->world->product->id);
});

it('reports the product type so the app does not treat every row as a service', function (): void {
    $rows = listProducts($this->world, ['product_id' => $this->world->product->id]);

    expect($rows[0]['type'])->toBe('product');
});

it('counts stock across every branch when no branch is asked for', function (): void {
    $second = Branch::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Second', 'code' => 'SB']);
    stockAt($this->world, $this->world->product, $second->id, 8);

    $rows = listProducts($this->world, ['product_id' => $this->world->product->id]);

    expect($rows[0]['total_stock'])->toEqual(20)
        ->and($rows[0]['stock_quantity_availability_status'])->toBe('in_stock');
});

it('counts only the requested branch when one is asked for', function (): void {
    $second = Branch::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Second', 'code' => 'SB']);
    stockAt($this->world, $this->world->product, $second->id, 8);

    $rows = listProducts($this->world, [
        'product_id' => $this->world->product->id,
        'branch_id' => $this->world->branch->id,
    ]);

    expect($rows[0]['total_stock'])->toEqual(12);
});

it('flags stock that is only available at another branch', function (): void {
    $second = Branch::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Second', 'code' => 'SB']);
    $elsewhere = makeProduct($this->world);
    // Sold out here, still on the shelf there. The branch filter keeps the row
    // in the list because a (zero) inventory row exists at the asked-for branch.
    stockAt($this->world, $elsewhere, $this->world->branch->id, 0);
    stockAt($this->world, $elsewhere, $second->id, 5);

    $rows = listProducts($this->world, [
        'product_id' => $elsewhere->id,
        'branch_id' => $this->world->branch->id,
    ]);

    expect($rows[0]['stock_quantity_availability_status'])->toBe('available_in_other_branches')
        ->and($rows[0]['total_stock'])->toEqual(0);
});

it('reports a product with no inventory row at all as out of stock', function (): void {
    $orphan = makeProduct($this->world);

    $rows = listProducts($this->world, ['product_id' => $orphan->id]);

    expect($rows[0]['total_stock'])->toEqual(0)
        ->and($rows[0]['is_out_of_stock'])->toBeTrue()
        ->and($rows[0]['stock_quantity_availability_status'])->toBe('out_of_stock');
});

it('falls back to the first normal image when no thumbnail is set', function (): void {
    DB::table('product_images')->insert([
        ['product_id' => $this->world->product->id, 'method' => 'normal', 'path' => 'https://cdn.test/first.png', 'name' => 'first', 'created_at' => now(), 'updated_at' => now()],
        ['product_id' => $this->world->product->id, 'method' => 'normal', 'path' => 'https://cdn.test/second.png', 'name' => 'second', 'created_at' => now(), 'updated_at' => now()],
        ['product_id' => $this->world->product->id, 'method' => 'angle', 'path' => 'https://cdn.test/spin.png', 'name' => 'spin', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $rows = listProducts($this->world, ['product_id' => $this->world->product->id]);

    expect($rows[0]['thumbnail'])->toBe('https://cdn.test/first.png');
});

it('prefers an explicit thumbnail over the image fallback', function (): void {
    $this->world->product->update(['thumbnail' => 'https://cdn.test/chosen.png']);
    DB::table('product_images')->insert([
        'product_id' => $this->world->product->id, 'method' => 'normal',
        'path' => 'https://cdn.test/other.png', 'name' => 'other',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $rows = listProducts($this->world, ['product_id' => $this->world->product->id]);

    expect($rows[0]['thumbnail'])->toBe('https://cdn.test/chosen.png');
});

it('keeps the list to the card shape, leaving the detail record to the detail routes', function (): void {
    $rows = listProducts($this->world, ['product_id' => $this->world->product->id]);

    expect($rows[0])->not->toHaveKeys(['inventories', 'images', 'images360', 'available_sizes', 'related_sizes'])
        ->and($rows[0])->toHaveKeys(['id', 'type', 'code', 'name', 'mrp', 'tax', 'thumbnail', 'total_stock']);
});

it('still returns the full record from the detail route', function (): void {
    $response = test()->getJson($this->world->url('/api/v1/products/'.$this->world->product->id));
    $response->assertSuccessful();

    expect($response->json('data'))->toHaveKeys(['inventories', 'available_sizes', 'related_sizes'])
        ->and($response->json('data.total_stock'))->toEqual(12);
});

it('leaves description out of search unless it is asked for', function (): void {
    $buried = makeProduct($this->world, [
        'name' => 'Unrelated Item',
        'description' => 'A comfortable running sandal',
    ]);

    expect(collect(listProducts($this->world, ['search' => 'sandal']))->pluck('id'))
        ->not->toContain($buried->id);

    expect(collect(listProducts($this->world, ['search' => 'sandal', 'search_in_description' => 1]))->pluck('id'))
        ->toContain($buried->id);
});

it('does not issue more queries as the page grows', function (): void {
    // The stock figures and the card photo come from aggregates and a subquery,
    // so a page of fifty costs the same number of round trips as a page of one.
    // A relation eager-load creeping back in would show up here first.
    foreach (range(1, 12) as $ignored) {
        stockAt($this->world, makeProduct($this->world), $this->world->branch->id, 3);
    }

    $count = function (int $perPage): int {
        cache()->flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        test()->getJson($this->world->url('/api/v1/products?in_stock_only=0&per_page='.$perPage))->assertSuccessful();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    expect($count(13))->toBe($count(1));
});
