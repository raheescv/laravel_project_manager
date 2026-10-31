<?php

use App\Livewire\Report\Employee\ProductivityReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * /report/employee_productivity — only completed, non-deleted sales count
 * towards an employee's totals, the summary cards and the top categories.
 *
 * @see App\Livewire\Report\Employee\ProductivityReport
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->sell = function (float $quantity, string $status, bool $deleted = false): void {
        $world = $this->world;
        $saleId = DB::table('sales')->insertGetId([
            'tenant_id' => $world->tenant->id,
            'invoice_no' => 'INV'.Str::upper(Str::random(6)),
            'branch_id' => $world->branch->id,
            'account_id' => $world->accounts['general_customer'],
            'date' => now()->toDateString(),
            'status' => $status,
            'created_by' => $world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deleted ? now() : null,
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
});

it('sums only completed sales', function (): void {
    ($this->sell)(2, 'completed');
    ($this->sell)(3, 'draft');
    ($this->sell)(4, 'cancelled');
    ($this->sell)(5, 'completed', deleted: true);

    $component = Livewire::test(ProductivityReport::class)
        ->assertSet('totalSales', 100.0)
        ->assertSet('totalTransactions', 1)
        ->assertSet('totalItems', 1);

    $employee = $component->get('employees')[$this->world->user->id];
    $categories = $component->viewData('topCategories')[$this->world->user->id];

    expect((float) $employee['total_sales'])->toEqual(100.0)
        ->and($employee['total_transactions'])->toBe(1)
        ->and($employee['items_sold'])->toBe(1)
        ->and($categories)->toHaveCount(1)
        ->and((int) $categories->first()->count)->toBe(1)
        ->and((float) $categories->first()->total)->toEqual(100.0);
});
