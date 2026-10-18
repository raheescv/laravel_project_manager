<?php

use App\Livewire\Purchase\View as PurchaseView;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The purchase view's Audit report tab mirrors the sale one: the
 * column-per-field Table (default) and the Timeline behind one switch.
 */
it('renders the table and timeline views inside the one audit report tab', function (): void {
    $world = PosWorld::create();
    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'tenant_id' => $world->tenant->id,
        'branch_id' => $world->branch->id,
        'account_id' => $world->cashAccountId,
        'invoice_no' => 'PB-'.uniqid(),
        'date' => now()->toDateString(),
        'total' => 100,
        'paid' => 0,
        'status' => 'completed',
        'created_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(PurchaseView::class, ['table_id' => $purchaseId])
        ->assertSee('data-bs-target="#tab-audit-report"', false)
        ->assertSeeInOrder(['Table', 'Timeline'])
        ->assertSee("localStorage.getItem('audit-report-view') || 'table'", false)
        ->assertSee('x-show="auditView === \'table\'"', false)
        ->assertSee('x-show="auditView === \'timeline\'"', false)
        ->assertSee('data-bs-target="#product-cost-audit"', false);
});
