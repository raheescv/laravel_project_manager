<?php

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Scopes\AssignedBranchScope;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * inventory-log:restore-sales recreates the "Sale:<invoice>" inventory log rows
 * of completed sales whose logs were deleted.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->inventory = Inventory::withoutGlobalScope(AssignedBranchScope::class)
        ->where('branch_id', $this->world->branch->id)
        ->where('product_id', $this->world->product->id)
        ->firstOrFail();
});

function rslSell(PosWorld $world, Inventory $inventory, string $invoice, float $quantity, string $date, string $status = 'completed'): int
{
    $saleId = DB::table('sales')->insertGetId([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => $invoice,
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => $date,
        'status' => $status,
        'created_by' => $world->user->id,
        'created_at' => $date.' 10:15:00',
        'updated_at' => $date.' 10:15:00',
    ]);

    DB::table('sale_items')->insert([
        'tenant_id' => $world->tenant->id,
        'sale_id' => $saleId,
        'employee_id' => $world->user->id,
        'inventory_id' => $inventory->id,
        'product_id' => $world->product->id,
        'unit_id' => $world->product->unit_id,
        'unit_price' => 50,
        'quantity' => $quantity,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $saleId;
}

function rslLog(PosWorld $world, Inventory $inventory, array $attributes): void
{
    $log = new InventoryLog($attributes + [
        'tenant_id' => $world->tenant->id,
        'branch_id' => $world->branch->id,
        'product_id' => $world->product->id,
        'quantity_in' => 0,
        'quantity_out' => 0,
        'barcode' => $inventory->barcode_number,
        'batch' => $inventory->batch,
        'cost' => 10,
        'user_id' => $world->user->id,
        'user_name' => $world->user->name,
    ]);
    $log->created_at = $attributes['created_at'];
    $log->save();
}

function rslSaleLogs(PosWorld $world)
{
    return InventoryLog::withoutGlobalScopes()
        ->where('tenant_id', $world->tenant->id)
        ->where('model', 'Sale')
        ->orderBy('created_at')
        ->get();
}

it('recreates missing sale logs with chained balances', function (): void {
    rslLog($this->world, $this->inventory, ['quantity_in' => 20, 'balance' => 20, 'model' => 'Purchase', 'model_id' => 1, 'remarks' => 'Purchase:1', 'created_at' => '2026-10-01 09:00:00']);
    $first = rslSell($this->world, $this->inventory, 'INV-1', 3, '2026-10-02');
    $second = rslSell($this->world, $this->inventory, 'INV-2', 2, '2026-10-03');
    rslSell($this->world, $this->inventory, 'INV-3', 5, '2026-10-04', 'cancelled');

    $this->artisan('inventory-log:restore-sales', ['tenant' => $this->world->tenant->id])->assertSuccessful();

    $logs = rslSaleLogs($this->world);
    expect($logs)->toHaveCount(2)
        ->and($logs->pluck('model_id')->all())->toBe([$first, $second])
        ->and($logs->pluck('remarks')->all())->toBe(['Sale:INV-1', 'Sale:INV-2'])
        ->and($logs->map(fn ($log) => (float) $log->quantity_out)->all())->toBe([3.0, 2.0])
        ->and($logs->map(fn ($log) => (float) $log->balance)->all())->toBe([17.0, 15.0])
        ->and($logs->first()->created_at->format('Y-m-d H:i:s'))->toBe('2026-10-02 10:15:00');
});

it('starts the balance from 0 when there is no earlier log', function (): void {
    rslSell($this->world, $this->inventory, 'INV-1', 3, '2026-10-02');

    $this->artisan('inventory-log:restore-sales', ['tenant' => $this->world->tenant->id, 'branch' => $this->world->branch->id])->assertSuccessful();

    expect((float) rslSaleLogs($this->world)->sole()->balance)->toBe(-3.0);
});

it('skips sales that still have their log and is safe to re-run', function (): void {
    $logged = rslSell($this->world, $this->inventory, 'INV-1', 3, '2026-10-02');
    rslLog($this->world, $this->inventory, ['quantity_out' => 3, 'balance' => 97, 'model' => 'Sale', 'model_id' => $logged, 'remarks' => 'Sale:INV-1', 'created_at' => '2026-10-02 10:15:00']);
    rslSell($this->world, $this->inventory, 'INV-2', 2, '2026-10-03');

    $this->artisan('inventory-log:restore-sales', ['tenant' => $this->world->tenant->id])->assertSuccessful();
    $this->artisan('inventory-log:restore-sales', ['tenant' => $this->world->tenant->id])->assertSuccessful();

    $logs = rslSaleLogs($this->world);
    expect($logs)->toHaveCount(2)
        ->and((float) $logs->last()->balance)->toBe(95.0);
});

it('writes nothing on a dry run', function (): void {
    rslSell($this->world, $this->inventory, 'INV-1', 3, '2026-10-02');

    $this->artisan('inventory-log:restore-sales', ['tenant' => $this->world->tenant->id, '--dry-run' => true])
        ->expectsOutputToContain('INV-1')
        ->assertSuccessful();

    expect(rslSaleLogs($this->world))->toHaveCount(0);
});
