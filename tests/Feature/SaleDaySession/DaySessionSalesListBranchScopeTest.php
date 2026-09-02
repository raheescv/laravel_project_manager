<?php

use App\Livewire\SaleDaySession\DaySessionSalesList;
use App\Models\SaleDaySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The Payments tab and collections-by-method on /sale/day-session/{id} are
 * built from date-scoped unions over sale_payments and tailoring_payments —
 * NOT from sale_day_session_id, so that dues collected today against an older
 * invoice still show up. That makes the branch constraint load-bearing: two
 * branches open sessions on the same date, and without it every payment taken
 * anywhere in the tenant lands on BOTH sessions' screens (the bug where two
 * same-day sessions double-counted each other's collections).
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);

    $this->branchB = $this->world->addBranch('Lusail Branch', 'LB');

    $today = Carbon::today();

    $this->sessionA = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => $today->copy()->setTime(9, 0),
        'opening_amount' => 100,
        'status' => 'open',
    ]);

    $this->sessionB = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->branchB->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => $today->copy()->setTime(9, 30),
        'opening_amount' => 100,
        'status' => 'open',
    ]);

    // One completed sale + payment per branch, both dated today. Inserted
    // through the query builder: `total`/`grand_total`/`balance` are STORED
    // GENERATED columns, so the models' fillable math is beside the point here.
    $makeSalePayment = function (int $branchId, int $sessionId, string $invoiceNo, float $amount) use ($today): void {
        $saleId = DB::table('sales')->insertGetId([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $branchId,
            'sale_day_session_id' => $sessionId,
            'account_id' => $this->world->accounts['general_customer'],
            'customer_name' => 'Walk-in Customer',
            'invoice_no' => $invoiceNo,
            'date' => $today->toDateString(),
            'status' => 'completed',
            'created_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sale_payments')->insert([
            'sale_id' => $saleId,
            'payment_method_id' => $this->world->cashAccountId,
            'date' => $today->toDateString(),
            'amount' => $amount,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $makeSalePayment($this->world->branch->id, $this->sessionA->id, 'INV-A-1', 150.00);
    $makeSalePayment($this->branchB->id, $this->sessionB->id, 'INV-B-1', 90.00);

    // A tailoring collection at branch B only — the tailoring union has the
    // same date-scoped shape and needs the same branch constraint.
    $orderId = DB::table('tailoring_orders')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->branchB->id,
        'sale_day_session_id' => $this->sessionB->id,
        'customer_name' => 'Thobe Customer',
        'order_no' => 'TLR-B-1',
        'order_date' => $today->toDateString(),
        'created_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tailoring_payments')->insert([
        'tenant_id' => $this->world->tenant->id,
        'tailoring_order_id' => $orderId,
        'payment_method_id' => $this->world->cashAccountId,
        'date' => $today->toDateString(),
        'amount' => 45.00,
        'created_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('keeps another branch\'s same-day payments out of the payments list and totals', function (): void {
    Livewire::test(DaySessionSalesList::class, ['sessionId' => $this->sessionA->id])
        ->assertOk()
        ->assertViewHas('combinedPaymentsTotal', fn ($total): bool => (float) $total === 150.00)
        ->assertViewHas('paymentSummaryTotal', fn ($total): bool => (float) $total === 150.00)
        ->assertSee('INV-A-1')
        ->assertDontSee('INV-B-1')
        ->assertDontSee('TLR-B-1');
});

it('shows the other branch\'s session only its own sale and tailoring collections', function (): void {
    Livewire::test(DaySessionSalesList::class, ['sessionId' => $this->sessionB->id])
        ->assertOk()
        ->assertViewHas('combinedPaymentsTotal', fn ($total): bool => (float) $total === 135.00)
        ->assertViewHas('paymentSummaryTotal', fn ($total): bool => (float) $total === 135.00)
        ->assertSee('INV-B-1')
        ->assertSee('TLR-B-1')
        ->assertDontSee('INV-A-1');
});
