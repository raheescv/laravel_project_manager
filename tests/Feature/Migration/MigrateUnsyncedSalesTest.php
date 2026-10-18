<?php

use App\Models\Tenant;
use App\Support\Migration\UnsyncedSales;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * migrate:database-data --only=sales --unsynced re-queues just the source sales whose invoice_no
 * is not in the target yet. UnsyncedSales is that check; the command's step selection and data
 * guard decide whether a re-run is allowed on an already-migrated database.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    $this->makeSale = fn (string $invoiceNo, array $attributes = []) => DB::table('sales')->insertGetId(array_merge([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'account_id' => $this->world->accounts['general_customer'],
        'customer_name' => 'Walk-in Customer',
        'invoice_no' => $invoiceNo,
        'date' => '2026-01-10',
        'gross_amount' => 100,
        'status' => 'completed',
        'created_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
});

it('keeps only source sales whose invoice_no is not in the target', function (): void {
    ($this->makeSale)('INV-1');
    ($this->makeSale)('INV-2', ['deleted_at' => now()]);

    $sourceSales = collect([
        (object) ['id' => 10, 'invoice_no' => 'INV-1'],
        (object) ['id' => 11, 'invoice_no' => 'INV-2'],
        (object) ['id' => 12, 'invoice_no' => 'INV-3'],
        (object) ['id' => 13, 'invoice_no' => null],
    ]);

    expect(UnsyncedSales::ids($sourceSales)->all())->toBe([12, 13]);
});

it('does not treat another tenant\'s invoice_no as synced', function (): void {
    $otherTenant = Tenant::factory()->create();

    ($this->makeSale)('INV-9', ['tenant_id' => $otherTenant->id]);

    expect(UnsyncedSales::ids(collect([(object) ['id' => 1, 'invoice_no' => 'INV-9']]))->all())->toBe([1]);
});

it('rejects an unknown --only step', function (): void {
    $this->artisan('migrate:database-data', ['--tenant' => $this->world->tenant->id, '--only' => 'sales,bogus'])
        ->expectsOutputToContain('Unknown step(s) in --only: bogus')
        ->assertFailed();
});

it('still refuses non-idempotent steps on a migrated database', function (): void {
    $this->artisan('migrate:database-data', ['--tenant' => $this->world->tenant->id, '--only' => 'sales,products'])
        ->expectsOutputToContain('Target database already contains data')
        ->assertFailed();
});
