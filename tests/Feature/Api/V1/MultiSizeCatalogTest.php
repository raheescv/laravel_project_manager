<?php

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * The showcase lets a customer pick several sizes at once, so /products,
 * /brands and /categories accept `sizes[]` beside the original single `size`.
 * Each must answer for the union of the sizes asked for — never only the first
 * or last, which is what PHP does with a repeated plain `size=` key.
 */
beforeEach(function (): void {
    cache()->flush();
    $this->world = PosWorld::create(stock: 10);
    DB::table('categories')->where('id', $this->world->product->main_category_id)->update(['online_visibility_flag' => true]);

    $this->brand = Brand::create(['name' => 'Size Run Brand']);
    $this->size42 = multiSizeProduct($this->world, $this->brand, '42');
    $this->size43 = multiSizeProduct($this->world, $this->brand, '43');
    $this->size44 = multiSizeProduct($this->world, $this->brand, '44');
});

function multiSizeProduct(PosWorld $world, Brand $brand, string $size): Product
{
    $product = Product::create([
        'tenant_id' => $world->tenant->id,
        'type' => 'product',
        'name' => 'Runner '.$size.' '.Str::random(4),
        'code' => 'M'.Str::upper(Str::random(6)),
        'size' => $size,
        'brand_id' => $brand->id,
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
        'quantity' => 5,
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

it('lists products in any of the sizes asked for', function (): void {
    $query = http_build_query(['sizes' => ['42', '44'], 'in_stock_only' => 0, 'per_page' => 100]);

    $ids = collect($this->getJson($this->world->url('/api/v1/products?'.$query))->assertSuccessful()->json('data.data'))
        ->pluck('id');

    expect($ids)->toContain($this->size42->id, $this->size44->id)
        ->not->toContain($this->size43->id);
});

it('still filters by a single size', function (): void {
    $ids = collect($this->getJson($this->world->url('/api/v1/products?size=43&in_stock_only=0&per_page=100'))->assertSuccessful()->json('data.data'))
        ->pluck('id');

    expect($ids->all())->toBe([$this->size43->id]);
});

it('counts a brand across every size asked for', function (): void {
    $query = http_build_query(['sizes' => ['42', '43']]);

    $brand = collect($this->getJson($this->world->url('/api/v1/brands?'.$query))->assertSuccessful()->json('data'))
        ->firstWhere('id', $this->brand->id);

    expect($brand['product_count'])->toBe(2);
});

it('counts a category across every size asked for', function (): void {
    $query = http_build_query(['sizes' => ['42', '43', '44']]);

    $category = collect($this->getJson($this->world->url('/api/v1/categories?'.$query))->assertSuccessful()->json('data'))
        ->firstWhere('id', $this->world->product->main_category_id);

    expect($category['product_count'])->toBe(3);
});

it('rejects a size list that is not a list', function (): void {
    $this->getJson($this->world->url('/api/v1/products?sizes=42'))->assertUnprocessable();
});
