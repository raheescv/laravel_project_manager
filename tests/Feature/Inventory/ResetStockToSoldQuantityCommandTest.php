<?php

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Scopes\AssignedBranchScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * inventory:reset-to-sold sets a branch's inventory quantity to the quantity
 * sold from it.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->second = $this->world->addBranch();
});

function rtsInventory(PosWorld $world, int $branchId): Inventory
{
    return Inventory::withoutGlobalScope(AssignedBranchScope::class)
        ->where('branch_id', $branchId)
        ->where('product_id', $world->product->id)
        ->firstOrFail();
}

function rtsSell(PosWorld $world, int $branchId, float $quantity, string $status = 'completed', string $date = '2026-10-01'): void
{
    $saleId = DB::table('sales')->insertGetId([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => 'INV-'.Str::random(8),
        'branch_id' => $branchId,
        'account_id' => $world->accounts['general_customer'],
        'date' => $date,
        'status' => $status,
        'created_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('sale_items')->insert([
        'tenant_id' => $world->tenant->id,
        'sale_id' => $saleId,
        'employee_id' => $world->user->id,
        'inventory_id' => rtsInventory($world, $branchId)->id,
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
}

it('sets the branch inventory to the completed sold quantity', function (): void {
    rtsSell($this->world, $this->world->branch->id, 3);
    rtsSell($this->world, $this->world->branch->id, 2);
    rtsSell($this->world, $this->world->branch->id, 7, 'cancelled');
    rtsSell($this->world, $this->second->id, 4);

    $this->artisan('inventory:reset-to-sold', ['tenant' => $this->world->tenant->id, 'branch' => $this->world->branch->id])->assertSuccessful();

    expect((float) rtsInventory($this->world, $this->world->branch->id)->quantity)->toBe(-5.0)
        ->and((float) rtsInventory($this->world, $this->second->id)->quantity)->toBe(100.0);
});

it('only counts sales inside the date range', function (): void {
    rtsSell($this->world, $this->world->branch->id, 3, 'completed', '2026-09-15');
    rtsSell($this->world, $this->world->branch->id, 2, 'completed', '2026-10-05');

    $this->artisan('inventory:reset-to-sold', [
        'tenant' => $this->world->tenant->id,
        'branch' => $this->world->branch->id,
        '--from' => '2026-10-01',
    ])->assertSuccessful();

    expect((float) rtsInventory($this->world, $this->world->branch->id)->quantity)->toBe(-2.0);
});

it('changes nothing on a dry run', function (): void {
    rtsSell($this->world, $this->world->branch->id, 3);

    $this->artisan('inventory:reset-to-sold', ['tenant' => $this->world->tenant->id, 'branch' => $this->world->branch->id, '--dry-run' => true])
        ->expectsOutputToContain($this->world->product->name)
        ->assertSuccessful();

    expect((float) rtsInventory($this->world, $this->world->branch->id)->quantity)->toBe(100.0);
});

it('fails for an unknown branch', function (): void {
    $this->artisan('inventory:reset-to-sold', ['tenant' => $this->world->tenant->id, 'branch' => 999999])->assertFailed();
});

it('fails when the branch belongs to another tenant', function (): void {
    $other = PosWorld::create();

    $this->artisan('inventory:reset-to-sold', ['tenant' => $other->tenant->id, 'branch' => $this->world->branch->id])->assertFailed();
});

it('resets every branch of the tenant when no branch is given', function (): void {
    rtsSell($this->world, $this->world->branch->id, 3);
    rtsSell($this->world, $this->second->id, 4);

    $this->artisan('inventory:reset-to-sold', ['tenant' => $this->world->tenant->id])->assertSuccessful();

    expect((float) rtsInventory($this->world, $this->world->branch->id)->quantity)->toBe(-3.0)
        ->and((float) rtsInventory($this->world, $this->second->id)->quantity)->toBe(-4.0);
});

it('writes an inventory log for every reset row', function (): void {
    rtsSell($this->world, $this->world->branch->id, 3);

    $this->artisan('inventory:reset-to-sold', ['tenant' => $this->world->tenant->id, 'branch' => $this->world->branch->id])->assertSuccessful();

    $log = InventoryLog::withoutGlobalScopes()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->where('model', 'StockReset')
        ->sole();

    expect((float) $log->quantity_out)->toBe(103.0)
        ->and((float) $log->quantity_in)->toBe(0.0)
        ->and((float) $log->balance)->toBe(-3.0)
        ->and($log->remarks)->toBe('Stock Reset: to sold quantity');
});
