<?php

use App\Livewire\Settings\Brand\Page;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Settings → Brand carries an Online Visibility switch, like categories do.
 * A hidden brand drops out of every storefront endpoint the showcase reads —
 * /brands, the /categories and /sizes counts, and /products when the caller
 * asks for `online_only` — while the POS catalog (no flag) still sees it.
 */
beforeEach(function (): void {
    cache()->flush();
    $this->world = PosWorld::create(stock: 10);
    DB::table('categories')->where('id', $this->world->product->main_category_id)->update(['online_visibility_flag' => true]);

    $this->visibleBrand = Brand::create(['name' => 'Visible Brand']);
    $this->hiddenBrand = Brand::create(['name' => 'Hidden Brand', 'online_visibility_flag' => false]);

    $this->visibleProduct = stockedBrandProduct($this->world, $this->visibleBrand, '41');
    $this->hiddenProduct = stockedBrandProduct($this->world, $this->hiddenBrand, '99');

    Sanctum::actingAs($this->world->user);
});

function stockedBrandProduct(PosWorld $world, Brand $brand, string $size, ?int $mainCategoryId = null): Product
{
    $product = Product::create([
        'tenant_id' => $world->tenant->id,
        'type' => 'product',
        'name' => $brand->name.' Shoe '.Str::random(4),
        'code' => 'B'.Str::upper(Str::random(6)),
        'size' => $size,
        'brand_id' => $brand->id,
        'unit_id' => $world->product->unit_id,
        'department_id' => $world->product->department_id,
        'main_category_id' => $mainCategoryId ?? $world->product->main_category_id,
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

it('leaves a hidden brand out of /brands', function (): void {
    $names = collect($this->getJson($this->world->url('/api/v1/brands'))->assertSuccessful()->json('data'))->pluck('name');

    expect($names)->toContain('Visible Brand')
        ->not->toContain('Hidden Brand');
});

it('does not count a hidden brand in the /categories product count', function (): void {
    $category = collect($this->getJson($this->world->url('/api/v1/categories'))->assertSuccessful()->json('data'))
        ->firstWhere('id', $this->world->product->main_category_id);

    $expected = Product::query()
        ->where('main_category_id', $this->world->product->main_category_id)
        ->where(fn ($q) => $q->whereNull('brand_id')->orWhere('brand_id', '!=', $this->hiddenBrand->id))
        ->count();

    expect($category['product_count'])->toBe($expected);
});

it('drops sizes only a hidden brand holds from /sizes', function (): void {
    $sizes = $this->getJson($this->world->url('/api/v1/sizes'))->assertSuccessful()->json('data');
    $all = collect([...$sizes['adult_sizes'], ...$sizes['young_sizes']])->pluck('size');

    expect($all)->toContain('41')
        ->not->toContain('99');
});

it('hides hidden brands and categories from /products only when online_only is asked', function (): void {
    $hiddenCategory = Category::create(['name' => 'Offline Only', 'online_visibility_flag' => false]);
    $offlineProduct = stockedBrandProduct($this->world, $this->visibleBrand, '42', $hiddenCategory->id);

    $storefront = collect($this->getJson($this->world->url('/api/v1/products?online_only=1&in_stock_only=0&per_page=100'))
        ->assertSuccessful()->json('data.data'))->pluck('id');

    expect($storefront)->toContain($this->visibleProduct->id)
        ->toContain($this->world->product->id)
        ->not->toContain($this->hiddenProduct->id)
        ->not->toContain($offlineProduct->id);

    cache()->flush();
    $pos = collect($this->getJson($this->world->url('/api/v1/products?in_stock_only=0&per_page=100'))
        ->assertSuccessful()->json('data.data'))->pluck('id');

    expect($pos)->toContain($this->hiddenProduct->id)
        ->toContain($offlineProduct->id);
});

it('saves the online visibility switch from the brand settings modal', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'brand.create', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);

    Livewire::test(Page::class)
        ->assertSet('brands.online_visibility_flag', true)
        ->assertSee('Online Visibility')
        ->set('brands.name', 'Offline Label')
        ->set('brands.online_visibility_flag', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Brand::firstWhere('name', 'Offline Label')->online_visibility_flag)->toBeFalse();
});
