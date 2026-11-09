<?php

use App\Exports\SaleItemReportExport;
use App\Livewire\Report\SaleItemReport;
use App\Models\UserHasBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * /report/sale_item — the Branch filter renders preselected with the session
 * branch, so the query must start scoped to that branch too.
 *
 * @see App\Livewire\Report\SaleItemReport
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->secondBranch = $this->world->addBranch();
    foreach ([$this->world->branch->id, $this->secondBranch->id] as $branchId) {
        UserHasBranch::create(['user_id' => $this->world->user->id, 'branch_id' => $branchId]);
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id, 'branch_name' => $this->world->branch->name]);

    $this->sellAt = function (int $branchId, float $quantity): void {
        $world = $this->world;
        $saleId = DB::table('sales')->insertGetId([
            'tenant_id' => $world->tenant->id,
            'invoice_no' => 'INV'.Str::upper(Str::random(6)),
            'branch_id' => $branchId,
            'account_id' => $world->accounts['general_customer'],
            'date' => now()->toDateString(),
            'status' => 'completed',
            'created_by' => $world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sale_items')->insert([
            'tenant_id' => $world->tenant->id,
            'sale_id' => $saleId,
            'employee_id' => $world->user->id,
            'inventory_id' => 0,
            'product_id' => $world->product->id,
            'unit_id' => $world->product->unit_id,
            'unit_price' => $world->product->mrp,
            'quantity' => $quantity,
            'discount' => 0,
            'tax' => 0,
            'created_by' => $world->user->id,
            'updated_by' => $world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    ($this->sellAt)($this->world->branch->id, 2);
    ($this->sellAt)($this->secondBranch->id, 3);
});

it('starts scoped to the session branch', function (): void {
    Livewire::test(SaleItemReport::class)
        ->assertSet('branch_id', $this->world->branch->id)
        ->assertViewHas('total', fn (array $total): bool => (float) $total['base_unit_quantity'] === 2.0);
});

it('covers every assigned branch when the filter is cleared', function (): void {
    Livewire::test(SaleItemReport::class)
        ->set('branch_id', null)
        ->assertViewHas('total', fn (array $total): bool => (float) $total['base_unit_quantity'] === 5.0);
});

it('shows the item discount and the invoice discount share separately', function (): void {
    $saleIds = DB::table('sales')->where('branch_id', $this->world->branch->id)->pluck('id');
    DB::table('sale_items')->whereIn('sale_id', $saleIds)->update(['unit_price' => 52, 'discount' => 4]);
    DB::table('sales')->whereIn('id', $saleIds)->update(['gross_amount' => 104, 'item_discount' => 4, 'tax_amount' => 0, 'other_discount' => 10]);

    Livewire::test(SaleItemReport::class)
        ->assertSeeInOrder(['-'.currency(4), 'Inv: -'.currency(10)])
        ->assertViewHas('total', fn (array $total): bool => (float) $total['discount'] === 4.0 && (float) $total['other_discount_share'] === 10.0);
});

it('exports the invoice discount share and effective total like the table', function (): void {
    $saleIds = DB::table('sales')->where('branch_id', $this->world->branch->id)->pluck('id');
    DB::table('sale_items')->whereIn('sale_id', $saleIds)->update(['unit_price' => 52, 'discount' => 4]);
    DB::table('sales')->whereIn('id', $saleIds)->update(['gross_amount' => 104, 'item_discount' => 4, 'tax_amount' => 0, 'other_discount' => 10]);

    $export = new SaleItemReportExport(['branch_id' => $this->world->branch->id]);
    $saleItem = $export->query()->sole();
    $row = array_combine($export->headings(), $export->map($saleItem));

    expect((float) $row['Discount'])->toBe(4.0)
        ->and((float) $row['Invoice Discount'])->toBe(10.0)
        ->and((float) $row['Total'])->toBe(100.0)
        ->and((float) $row['Effective Total'])->toBe(90.0)
        ->and((float) $row['Invoice Discount'])->toBe((float) $saleItem->other_discount_share)
        ->and((float) $row['Effective Total'])->toBe((float) $saleItem->effective_total);
});
