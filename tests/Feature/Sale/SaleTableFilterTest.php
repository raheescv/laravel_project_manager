<?php

use App\Livewire\Sale\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The sale list's date range can run on either the invoice date the user typed
 * or the moment the row was actually saved. A back-dated or imported sale falls
 * on a different day under each, so the list carries a "Based On" selector.
 *
 * @see dateBasisOptions()
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

function insertListedSale(PosWorld $world, string $invoiceNo, string $date, string $createdAt): int
{
    return DB::table('sales')->insertGetId([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => $invoiceNo,
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => $date,
        'status' => 'completed',
        'gross_amount' => 50,
        'paid' => 50,
        'created_by' => $world->user->id,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

it('runs the date range on the invoice date or the created-at stamp', function (): void {
    insertListedSale($this->world, 'INVBACKDATED', now()->subDay()->toDateString(), now()->toDateTimeString());
    insertListedSale($this->world, 'INVTODAY', now()->toDateString(), now()->toDateTimeString());

    Livewire::test(Table::class)
        ->assertSet('based_on', 'date')
        ->assertSee('INVTODAY')
        ->assertDontSee('INVBACKDATED')
        ->set('based_on', 'created_at')
        ->assertSee('INVTODAY')
        ->assertSee('INVBACKDATED');
});

it('hides a sale saved after the range even when its invoice date is in it', function (): void {
    insertListedSale($this->world, 'INVLATESAVE', now()->toDateString(), now()->addDay()->toDateTimeString());

    Livewire::test(Table::class)
        ->assertSee('INVLATESAVE')
        ->set('based_on', 'created_at')
        ->assertDontSee('INVLATESAVE');
});

it('adds and clears a whole run of rows for a shift-click range', function (): void {
    $ids = collect(range(1, 4))
        ->map(fn ($n) => insertListedSale($this->world, "INVRANGE{$n}", now()->toDateString(), now()->toDateTimeString()))
        ->map(fn ($id) => (string) $id)
        ->all();

    Livewire::test(Table::class)
        ->set('selected', [$ids[0]])
        ->call('toggleRange', array_slice($ids, 0, 3), true)
        ->assertSet('selected', array_slice($ids, 0, 3))
        ->call('toggleRange', array_slice($ids, 1, 2), false)
        ->assertSet('selected', [$ids[0]]);
});
