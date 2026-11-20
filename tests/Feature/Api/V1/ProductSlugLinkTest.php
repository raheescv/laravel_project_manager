<?php

use App\Models\Brand;
use App\Models\Product;
use App\Support\StorefrontSlug;
use Tests\Support\PosWorld;

/**
 * The showcase website puts names in its URLs, never ids: #/product/running-shoe-pro
 * and #/?brand=new-balance. A name that cannot make a slug goes in as itself, so the
 * API reads every link back by exact name first, then by slug.
 */
beforeEach(function (): void {
    cache()->flush();
    $this->world = PosWorld::create(stock: 5);
    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe Pro', 'size' => '41']);
});

it('gives every product row the name link its page is opened by', function (): void {
    $this->getJson($this->world->url('/api/v1/products?in_stock_only=0&search=Running'))
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.slug', 'running-shoe-pro');
});

it('opens a product page from its slug', function (): void {
    $this->getJson($this->world->url('/api/v1/products/running-shoe-pro'))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->shoe->id)
        ->assertJsonPath('data.total_stock', 5);
});

it('opens a product page from a name that cannot make a slug', function (): void {
    $this->shoe->update(['name' => 'حذاء رياضي']);

    $this->getJson($this->world->url('/api/v1/products/'.rawurlencode('حذاء رياضي')))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->shoe->id)
        ->assertJsonPath('data.slug', 'حذاء رياضي');
});

it('still opens an old id link', function (): void {
    $this->getJson($this->world->url('/api/v1/products/'.$this->shoe->id))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->shoe->id);
});

it('lands a shared variant name on the family head', function (): void {
    Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    $this->getJson($this->world->url('/api/v1/products/running-shoe-pro'))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->shoe->id);
});

it('does not mistake a longer name for the one asked for', function (): void {
    $this->shoe->update(['name' => 'Running Shoe Pro Max']);

    $this->getJson($this->world->url('/api/v1/products/running-shoe-pro'))
        ->assertServerError();
});

it('filters the catalogue by brand name instead of id', function (): void {
    $brand = Brand::create(['name' => 'New Balance']);
    $this->shoe->update(['brand_id' => $brand->id]);
    $other = Product::factory()->create([
        'name' => 'Other Pair',
        'tenant_id' => $this->world->tenant->id,
        'unit_id' => $this->shoe->unit_id,
        'department_id' => $this->shoe->department_id,
        'main_category_id' => $this->shoe->main_category_id,
    ]);

    $ids = fn (string $link) => collect($this->getJson($this->world->url('/api/v1/products?in_stock_only=0&brand='.rawurlencode($link)))
        ->assertSuccessful()->json('data.data'))->pluck('id')->all();

    expect($ids('new-balance'))->toBe([$this->shoe->id])
        ->and($ids('New Balance'))->toBe([$this->shoe->id])
        ->and($ids('no-such-brand'))->toBe([])
        ->and($ids(''))->toContain($other->id);
});

it('gives every brand tile the name link the catalogue URL carries', function (): void {
    $brand = Brand::create(['name' => 'New Balance']);
    $this->shoe->update(['brand_id' => $brand->id]);

    $tile = collect($this->getJson($this->world->url('/api/v1/brands?available_products_only=0'))->assertSuccessful()->json('data'))
        ->firstWhere('id', $brand->id);

    expect($tile['slug'])->toBe('new-balance');
});

it('keeps a name as itself when it cannot make a slug', function (string $name): void {
    expect(StorefrontSlug::make($name))->toBeNull()
        ->and(StorefrontSlug::link($name))->toBe(trim($name));
})->with([
    'arabic' => 'حذاء رياضي',
    'digits only' => '2024',
]);

it('slugs names the way the link reads', function (string $name, string $slug): void {
    expect(StorefrontSlug::link($name))->toBe($slug);
})->with([
    ['Nike Air-Max 90', 'nike-air-max-90'],
    ['Café Noir', 'cafe-noir'],
    ['Shoe & Co. (Kids)', 'shoe-co-kids'],
]);
