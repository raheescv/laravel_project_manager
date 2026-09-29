<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * GET /api/v1/sizes carries units sold per size so the showcase can lead its
 * size run with the fastest movers.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 50);
    DB::table('categories')->where('id', $this->world->product->main_category_id)->update(['online_visibility_flag' => true]);
    Sanctum::actingAs($this->world->user);
});

function sizedProduct(PosWorld $world, string $size): Product
{
    $product = Product::create([
        'tenant_id' => $world->tenant->id,
        'type' => 'product',
        'name' => 'Shoe '.$size.' '.Str::random(4),
        'code' => 'S'.Str::upper(Str::random(6)),
        'size' => $size,
        'unit_id' => $world->product->unit_id,
        'department_id' => $world->product->department_id,
        'main_category_id' => $world->product->main_category_id,
        'mrp' => 50,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
    ]);

    DB::table('inventories')->insert([
        'tenant_id' => $world->tenant->id,
        'branch_id' => $world->branch->id,
        'product_id' => $product->id,
        'quantity' => 20,
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

function sell(PosWorld $world, Product $product, int $quantity): void
{
    test()->postJson($world->url('/api/v1/sale'), $world->salePayload([
        'items' => [[
            'productId' => $product->id,
            'quantity' => $quantity,
            'unitPrice' => 50,
            'discount' => 0,
        ]],
        'totalPayment' => 50 * $quantity,
    ]))->assertSuccessful();
}

it('reports units sold per size from completed sales', function (): void {
    $forty = sizedProduct($this->world, '40');
    $fortyTwo = sizedProduct($this->world, '42');
    sizedProduct($this->world, '44');

    sell($this->world, $fortyTwo, 3);
    sell($this->world, $fortyTwo, 2);
    sell($this->world, $forty, 1);

    $sizes = collect(test()->getJson($this->world->url('/api/v1/sizes'))
        ->assertSuccessful()
        ->json('data.adult_sizes'))
        ->pluck('sold_qty', 'size');

    expect($sizes->get('42'))->toBe(5)
        ->and($sizes->get('40'))->toBe(1)
        ->and($sizes->get('44'))->toBe(0);
});
