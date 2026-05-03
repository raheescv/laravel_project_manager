<?php

use App\Livewire\Report\StockAnalysisReport;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

function createStockAnalysisProductFixture(int $tenantId, string $name, string $code, array $attributes = []): int
{
    $now = now();
    $unitId = DB::table('units')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Unit '.fake()->unique()->bothify('###'),
        'code' => fake()->unique()->bothify('U###'),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $departmentId = DB::table('departments')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Department '.fake()->unique()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $categoryId = DB::table('categories')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => $attributes['main_category_name'] ?? 'Category '.fake()->unique()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $subCategoryId = null;
    if (isset($attributes['sub_category_name'])) {
        $subCategoryId = DB::table('categories')->insertGetId([
            'tenant_id' => $tenantId,
            'parent_id' => $categoryId,
            'name' => $attributes['sub_category_name'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    $brandId = null;
    if (isset($attributes['brand_name'])) {
        $brandId = DB::table('brands')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $attributes['brand_name'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    return DB::table('products')->insertGetId([
        'tenant_id' => $tenantId,
        'type' => 'product',
        'name' => $name,
        'code' => $code,
        'unit_id' => $unitId,
        'department_id' => $departmentId,
        'main_category_id' => $categoryId,
        'sub_category_id' => $subCategoryId,
        'brand_id' => $brandId,
        'cost' => 10,
        'mrp' => 15,
        'barcode_number' => $attributes['barcode_number'] ?? fake()->unique()->numerify('########'),
        'size' => $attributes['size'] ?? null,
        'created_by' => 1,
        'updated_by' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function createStockAnalysisBranchFixture(int $tenantId, string $name, ?string $code = null): int
{
    return DB::table('branches')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => $name,
        'code' => $code ?? fake()->unique()->bothify('BR###'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createStockAnalysisInventoryLogFixture(int $tenantId, int $branchId, int $productId, float $quantityOut): void
{
    DB::table('inventory_logs')->insert([
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'product_id' => $productId,
        'quantity_in' => 0,
        'quantity_out' => $quantityOut,
        'balance' => 0,
        'barcode' => fake()->unique()->numerify('########'),
        'batch' => fake()->uuid(),
        'cost' => 10,
        'user_id' => 1,
        'user_name' => 'Tester',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('builds top moving chart data separately for each product branch', function (): void {
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Test Tenant '.fake()->uuid(),
        'code' => fake()->uuid(),
        'subdomain' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(TenantService::class)->setCurrentTenant(Tenant::query()->findOrFail($tenantId));

    $firstBranchId = createStockAnalysisBranchFixture($tenantId, 'First Branch', 'FST');
    $secondBranchId = createStockAnalysisBranchFixture($tenantId, 'Second Branch', 'SCD');
    $productId = createStockAnalysisProductFixture($tenantId, 'Duplicated Product', 'DUP-001');
    $otherProductId = createStockAnalysisProductFixture($tenantId, 'Other Product', 'OTH-001');

    createStockAnalysisInventoryLogFixture($tenantId, $firstBranchId, $productId, 2);
    createStockAnalysisInventoryLogFixture($tenantId, $secondBranchId, $productId, 3);
    createStockAnalysisInventoryLogFixture($tenantId, $firstBranchId, $otherProductId, 4);

    $component = new StockAnalysisReport();
    $component->from_date = now()->subDay()->toDateString();
    $component->to_date = now()->addDay()->toDateString();
    $component->limit = 10;

    $chartData = $component->getChartData();

    expect($chartData['labels'])->toBe(['Other Product (FST)', 'Duplicated Product (SCD)', 'Duplicated Product (FST)'])
        ->and($chartData['datasets'][0]['data'])->toBe([4.0, 3.0, 2.0]);
});

it('filters top moving products by product text search', function (string $search): void {
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Test Tenant '.fake()->uuid(),
        'code' => fake()->uuid(),
        'subdomain' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(TenantService::class)->setCurrentTenant(Tenant::query()->findOrFail($tenantId));

    $branchId = createStockAnalysisBranchFixture($tenantId, 'Main Branch', 'MAIN');
    $matchingProductId = createStockAnalysisProductFixture($tenantId, 'Red Shirt', 'SKU-RED', [
        'barcode_number' => 'BAR-RED-001',
        'brand_name' => 'Acme Brand',
        'main_category_name' => 'Menswear',
        'sub_category_name' => 'Formal Shirts',
        'size' => 'XL',
    ]);
    $otherProductId = createStockAnalysisProductFixture($tenantId, 'Blue Trousers', 'SKU-BLUE', [
        'barcode_number' => 'BAR-BLUE-001',
        'brand_name' => 'Other Brand',
        'main_category_name' => 'Bottomwear',
        'sub_category_name' => 'Trousers',
        'size' => 'M',
    ]);

    createStockAnalysisInventoryLogFixture($tenantId, $branchId, $matchingProductId, 8);
    createStockAnalysisInventoryLogFixture($tenantId, $branchId, $otherProductId, 12);

    $component = new StockAnalysisReport();
    $component->from_date = now()->subDay()->toDateString();
    $component->to_date = now()->addDay()->toDateString();
    $component->limit = 10;
    $component->product_search = $search;

    $chartData = $component->getChartData();

    expect($chartData['labels'])->toBe(['Red Shirt (MAIN)'])
        ->and($chartData['datasets'][0]['data'])->toBe([8.0]);
})->with([
    'name' => 'Red Shirt',
    'sku' => 'SKU-RED',
    'barcode' => 'BAR-RED',
    'size' => 'XL',
]);

it('filters top moving products by product dropdown filters', function (string $filter, string $column): void {
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Test Tenant '.fake()->uuid(),
        'code' => fake()->uuid(),
        'subdomain' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(TenantService::class)->setCurrentTenant(Tenant::query()->findOrFail($tenantId));

    $branchId = createStockAnalysisBranchFixture($tenantId, 'Main Branch', 'MAIN');
    $matchingProductId = createStockAnalysisProductFixture($tenantId, 'Red Shirt', 'SKU-RED', [
        'barcode_number' => 'BAR-RED-001',
        'brand_name' => 'Acme Brand',
        'main_category_name' => 'Menswear',
        'sub_category_name' => 'Formal Shirts',
        'size' => 'XL',
    ]);
    $otherProductId = createStockAnalysisProductFixture($tenantId, 'Blue Trousers', 'SKU-BLUE', [
        'barcode_number' => 'BAR-BLUE-001',
        'brand_name' => 'Other Brand',
        'main_category_name' => 'Bottomwear',
        'sub_category_name' => 'Trousers',
        'size' => 'M',
    ]);

    createStockAnalysisInventoryLogFixture($tenantId, $branchId, $matchingProductId, 8);
    createStockAnalysisInventoryLogFixture($tenantId, $branchId, $otherProductId, 12);

    $product = DB::table('products')->where('id', $matchingProductId)->first();

    $component = new StockAnalysisReport();
    $component->from_date = now()->subDay()->toDateString();
    $component->to_date = now()->addDay()->toDateString();
    $component->limit = 10;
    $component->{$filter} = $product->{$column};

    $chartData = $component->getChartData();

    expect($chartData['labels'])->toBe(['Red Shirt (MAIN)'])
        ->and($chartData['datasets'][0]['data'])->toBe([8.0]);
})->with([
    'category' => ['main_category_id', 'main_category_id'],
    'sub category' => ['sub_category_id', 'sub_category_id'],
    'brand' => ['brand_id', 'brand_id'],
]);

it('selects joined main category sub category and brand labels on top moving rows', function (): void {
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Test Tenant '.fake()->uuid(),
        'code' => fake()->uuid(),
        'subdomain' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(TenantService::class)->setCurrentTenant(Tenant::query()->findOrFail($tenantId));

    $branchId = createStockAnalysisBranchFixture($tenantId, 'Main Branch');
    $redProductId = createStockAnalysisProductFixture($tenantId, 'Red Shirt', 'SKU-TAXON', [
        'barcode_number' => 'BAR-TAXON-001',
        'brand_name' => 'Acme Brand',
        'main_category_name' => 'Menswear',
        'sub_category_name' => 'Formal Shirts',
    ]);
    createStockAnalysisProductFixture($tenantId, 'Other Product', 'SKU-OTHER', []);

    createStockAnalysisInventoryLogFixture($tenantId, $branchId, $redProductId, 100);

    $component = new StockAnalysisReport();
    $component->report_type = 'top_moving';
    $component->from_date = now()->subDay()->toDateString();
    $component->to_date = now()->addDay()->toDateString();
    $component->limit = 10;

    $products = $component->render()->getData()['products'];

    $redRow = collect($products)->firstWhere('code', 'SKU-TAXON');

    expect($redRow)->not->toBeNull()
        ->and($redRow->main_category_name)->toBe('Menswear')
        ->and($redRow->sub_category_name)->toBe('Formal Shirts')
        ->and($redRow->brand_name)->toBe('Acme Brand');
});
