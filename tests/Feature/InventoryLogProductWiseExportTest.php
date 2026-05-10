<?php

use App\Exports\InventoryLogProductWiseExport;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    app(TenantService::class)->clearCurrentTenant();
});

function createInventoryLogProductWiseFixture(): array
{
    $now = now();
    $tenantId = DB::table('tenants')->insertGetId([
        'name' => 'Inventory Export Tenant',
        'code' => 'inventory-export-tenant',
        'subdomain' => 'inventory-export',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    app(TenantService::class)->setCurrentTenant(Tenant::findOrFail($tenantId));

    $branchId = DB::table('branches')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Main Branch',
        'code' => 'MAIN',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $departmentId = DB::table('departments')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Inventory Department',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $unitId = DB::table('units')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Piece',
        'code' => 'PCS',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $categoryId = DB::table('categories')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Inventory Category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $firstProductId = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId,
        'type' => 'product',
        'name' => 'First Product',
        'code' => 'FIRST',
        'unit_id' => $unitId,
        'department_id' => $departmentId,
        'main_category_id' => $categoryId,
        'cost' => 7,
        'mrp' => 12,
        'created_by' => 1,
        'updated_by' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $secondProductId = DB::table('products')->insertGetId([
        'tenant_id' => $tenantId,
        'type' => 'product',
        'name' => 'Second Product',
        'code' => 'SECOND',
        'unit_id' => $unitId,
        'department_id' => $departmentId,
        'main_category_id' => $categoryId,
        'cost' => 20,
        'mrp' => 25,
        'created_by' => 1,
        'updated_by' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $oldFirstLogId = createInventoryLogProductWiseLog($tenantId, $branchId, $firstProductId, 5, 0, 5, 7, '2026-02-01 09:00:00');
    $latestFirstLogId = createInventoryLogProductWiseLog($tenantId, $branchId, $firstProductId, 12, 2, 15, 7, '2026-02-10 17:00:00');
    $futureFirstLogId = createInventoryLogProductWiseLog($tenantId, $branchId, $firstProductId, 99, 0, 99, 7, '2026-02-12 09:00:00');
    $latestSecondLogId = createInventoryLogProductWiseLog($tenantId, $branchId, $secondProductId, 3, 1, 3, 20, '2026-02-08 12:00:00');

    return [
        'old_first_log_id' => $oldFirstLogId,
        'latest_first_log_id' => $latestFirstLogId,
        'future_first_log_id' => $futureFirstLogId,
        'latest_second_log_id' => $latestSecondLogId,
        'branch_id' => $branchId,
    ];
}

function createInventoryLogProductWiseLog(
    int $tenantId,
    int $branchId,
    int $productId,
    float $quantityIn,
    float $quantityOut,
    float $balance,
    float $cost,
    string $createdAt
): int {
    return DB::table('inventory_logs')->insertGetId([
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'product_id' => $productId,
        'quantity_in' => $quantityIn,
        'quantity_out' => $quantityOut,
        'balance' => $balance,
        'barcode' => 'BC-'.$productId,
        'batch' => 'BATCH-'.$productId,
        'cost' => $cost,
        'remarks' => 'Stock balance',
        'user_id' => 1,
        'user_name' => 'Tester',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

it('exports one latest inventory log per product as of the end date with totals', function (): void {
    $fixture = createInventoryLogProductWiseFixture();

    $export = new InventoryLogProductWiseExport([
        'to_date' => '2026-02-10',
        'branch_id' => $fixture['branch_id'],
    ]);

    $rows = $export->collection();

    expect($rows)->toHaveCount(3)
        ->and($rows->pluck('id')->filter()->all())->toBe([
            $fixture['latest_first_log_id'],
            $fixture['latest_second_log_id'],
        ])
        ->and($rows->pluck('id')->filter()->all())->not->toContain($fixture['old_first_log_id'])
        ->and($rows->pluck('id')->filter()->all())->not->toContain($fixture['future_first_log_id']);

    $summary = $rows->last();

    expect($summary->is_summary)->toBeTrue()
        ->and((float) $summary->quantity_in)->toBe(15.0)
        ->and((float) $summary->quantity_out)->toBe(3.0)
        ->and((float) $summary->balance)->toBe(18.0)
        ->and((float) $summary->total_cost)->toBe(165.0);
});
