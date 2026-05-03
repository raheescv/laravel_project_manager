<?php

use App\Actions\Product\Inventory\GetAction;
use Illuminate\Support\Facades\DB;

function createInventoryFilterFixtureProduct(array $overrides = []): int
{
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Test Tenant '.fake()->uuid(),
        'code' => fake()->uuid(),
        'subdomain' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $branchId = DB::table('branches')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Branch '.fake()->uuid(),
        'code' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $departmentId = DB::table('departments')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Department '.fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $unitId = DB::table('units')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => fake()->bothify('Unit####'),
        'code' => fake()->bothify('U####'),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $mainCategoryId = DB::table('categories')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Category '.fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $productId = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId,
        'type' => 'product',
        'name' => $overrides['name'],
        'code' => fake()->uuid(),
        'unit_id' => $unitId,
        'department_id' => $departmentId,
        'main_category_id' => $mainCategoryId,
        'cost' => 10,
        'mrp' => 15,
        'barcode_number' => fake()->numerify('########'),
        'created_by' => 1,
        'updated_by' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('inventories')->insert([
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'product_id' => $productId,
        'quantity' => 5,
        'barcode_number' => fake()->numerify('########'),
        'batch' => fake()->uuid(),
        'cost' => 10,
        'created_by' => 1,
        'updated_by' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    if ($overrides['with_image'] ?? false) {
        DB::table('product_images')->insert([
            'product_id' => $productId,
            'path' => 'products/test.jpg',
            'name' => 'test.jpg',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    return $productId;
}

it('filters inventory products by image status', function (): void {
    createInventoryFilterFixtureProduct([
        'name' => 'Has Image Product',
        'with_image' => true,
    ]);
    createInventoryFilterFixtureProduct([
        'name' => 'No Image Product',
        'with_image' => false,
    ]);

    $baseFilters = [
        'stock_quantity_filter' => 'all',
        'sortField' => 'inventories.id',
        'sortDirection' => 'asc',
    ];

    $withImageProducts = (new GetAction())
        ->execute([...$baseFilters, 'image_status' => 'with_image'])
        ->pluck('products.name')
        ->all();

    $withoutImageProducts = (new GetAction())
        ->execute([...$baseFilters, 'image_status' => 'without_image'])
        ->pluck('products.name')
        ->all();

    expect($withImageProducts)
        ->toContain('Has Image Product')
        ->not->toContain('No Image Product')
        ->and($withoutImageProducts)
        ->toContain('No Image Product')
        ->not->toContain('Has Image Product');
});
