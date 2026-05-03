<?php

use App\Livewire\Report\StockAnalysisReport;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

function createStockAnalysisProductFixture(int $tenantId, string $name, string $code): int
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
        'name' => 'Category '.fake()->unique()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return DB::table('products')->insertGetId([
        'tenant_id' => $tenantId,
        'type' => 'product',
        'name' => $name,
        'code' => $code,
        'unit_id' => $unitId,
        'department_id' => $departmentId,
        'main_category_id' => $categoryId,
        'cost' => 10,
        'mrp' => 15,
        'barcode_number' => fake()->unique()->numerify('########'),
        'created_by' => 1,
        'updated_by' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function createStockAnalysisBranchFixture(int $tenantId, string $name): int
{
    return DB::table('branches')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => $name,
        'code' => fake()->unique()->bothify('BR###'),
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

it('builds top moving chart data with unique product ids across branches', function (): void {
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Test Tenant '.fake()->uuid(),
        'code' => fake()->uuid(),
        'subdomain' => fake()->uuid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(TenantService::class)->setCurrentTenant(Tenant::query()->findOrFail($tenantId));

    $firstBranchId = createStockAnalysisBranchFixture($tenantId, 'First Branch');
    $secondBranchId = createStockAnalysisBranchFixture($tenantId, 'Second Branch');
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

    expect($chartData['labels'])->toBe(['Duplicated Product', 'Other Product'])
        ->and($chartData['datasets'][0]['data'])->toBe([5.0, 4.0]);
});
