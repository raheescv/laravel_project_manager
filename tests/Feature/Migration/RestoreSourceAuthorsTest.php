<?php

use App\Models\Inventory;
use App\Models\Sale;
use App\Models\User;
use App\Support\Migration\SourceAuthors;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * The migration replays sales as the system user, so the create action stamps created_by with it.
 * SourceAuthors puts the old system's real cashier back on each sale ("Created By"), and keeps the
 * user names spelt exactly as the old system had them.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    $this->makeUser = fn (int $sourceId, string $name, string $type = 'user') => User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'type' => $type,
        'second_reference_no' => $sourceId,
        'name' => $name,
    ]);

    $this->makeSale = fn (string $invoiceNo) => DB::table('sales')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'account_id' => $this->world->accounts['general_customer'],
        'customer_name' => 'Walk-in Customer',
        'invoice_no' => $invoiceNo,
        'date' => '2026-01-10',
        'gross_amount' => 100,
        'status' => 'completed',
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->makeItem = fn (int $saleId) => DB::table('sale_items')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'sale_id' => $saleId,
        'employee_id' => $this->world->user->id,
        'inventory_id' => Inventory::where('product_id', $this->world->product->id)->value('id'),
        'product_id' => $this->world->product->id,
        'unit_price' => 100,
        'quantity' => 1,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('back-stamps a replayed sale and its lines with the source author', function (): void {
    $cashier = ($this->makeUser)(7, 'Haneefa Babu', 'employee');
    $sale = Sale::find(($this->makeSale)('INV-1'));
    $item = ($this->makeItem)($sale->id);

    SourceAuthors::applyToSale($sale, $cashier->id, null);

    expect(DB::table('sales')->where('id', $sale->id)->first())
        ->created_by->toBe($cashier->id)
        ->updated_by->toBe($this->world->user->id)
        ->and(DB::table('sale_items')->where('id', $item)->first())
        ->created_by->toBe($cashier->id)
        ->updated_by->toBe($this->world->user->id)
        ->employee_id->toBe($this->world->user->id);
});

it('resolves an old author through the table its model names, not just its id', function (): void {
    $habid = ($this->makeUser)(10, 'Habid');
    $makbul = ($this->makeUser)(10, 'MAKBUL', 'employee');

    $userMap = User::where('type', 'user')->pluck('id', 'second_reference_no');
    $employeeMap = User::where('type', 'employee')->pluck('id', 'second_reference_no');

    expect(SourceAuthors::resolve('Employee', 10, $userMap, $employeeMap))->toBe($makbul->id)
        ->and(SourceAuthors::resolve('User', 10, $userMap, $employeeMap))->toBe($habid->id)
        ->and(SourceAuthors::resolve(null, 10, $userMap, $employeeMap))->toBe($habid->id)
        ->and(SourceAuthors::resolve('Employee', 99, $userMap, $employeeMap))->toBeNull()
        ->and(SourceAuthors::resolve('Employee', null, $userMap, $employeeMap))->toBeNull();
});

it('re-points already migrated sales at their source authors', function (): void {
    $habid = ($this->makeUser)(10, 'Habid');
    $makbul = ($this->makeUser)(10, 'MAKBUL', 'employee');
    $jabir = ($this->makeUser)(17, 'JABIR', 'employee');
    $first = ($this->makeSale)('INV-1');
    $second = ($this->makeSale)('INV-2');
    $orphan = ($this->makeSale)('INV-3');
    $firstItem = ($this->makeItem)($first);
    $orphanItem = ($this->makeItem)($orphan);

    $userMap = User::where('type', 'user')->pluck('id', 'second_reference_no');
    $employeeMap = User::where('type', 'employee')->pluck('id', 'second_reference_no');
    $updated = SourceAuthors::restoreSales($userMap, $employeeMap, collect([
        (object) ['invoice_no' => 'INV-1', 'created_model' => 'Employee', 'created_by' => 10, 'updated_model' => 'User', 'updated_by' => 10],
        (object) ['invoice_no' => 'INV-2', 'created_model' => 'Employee', 'created_by' => 17, 'updated_model' => 'Employee', 'updated_by' => null],
        (object) ['invoice_no' => 'INV-3', 'created_model' => 'Employee', 'created_by' => 99, 'updated_model' => 'Employee', 'updated_by' => 99],
    ]));

    expect($updated)->toBe(2)
        ->and(DB::table('sales')->where('id', $first)->first())
        ->created_by->toBe($makbul->id)
        ->updated_by->toBe($habid->id)
        ->and(DB::table('sales')->where('id', $second)->first())
        ->created_by->toBe($jabir->id)
        ->updated_by->toBe($this->world->user->id)
        ->and(DB::table('sales')->where('id', $orphan)->value('created_by'))->toBe($this->world->user->id)
        ->and(DB::table('sale_items')->where('id', $firstItem)->first())
        ->created_by->toBe($makbul->id)
        ->updated_by->toBe($habid->id)
        ->and(DB::table('sale_items')->where('id', $orphanItem)->value('created_by'))->toBe($this->world->user->id);
});

it('restores user names exactly as the old system spells them', function (): void {
    $hasre = ($this->makeUser)(7, 'Hasre');
    $harshid = ($this->makeUser)(8, 'Harshid vt');

    $userMap = User::where('type', 'user')->pluck('id', 'second_reference_no');
    SourceAuthors::restoreUserNames($userMap, collect([7 => 'HASRE', 8 => 'Harshid VT', 99 => 'Unmigrated']));

    expect($hasre->fresh()->name)->toBe('HASRE')
        ->and($harshid->fresh()->name)->toBe('Harshid VT');
});

it('accepts sale-authors as a step that may re-run on a migrated database', function (): void {
    $this->artisan('migrate:database-data', ['--tenant' => $this->world->tenant->id, '--only' => 'sale-authors,products'])
        ->expectsOutputToContain('Target database already contains data')
        ->assertFailed();
});
