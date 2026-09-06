<?php

use App\Actions\Sale\ChangeDaySessionAction;
use App\Livewire\Sale\ChangeSession;
use App\Models\Sale;
use App\Models\SaleDaySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * sale:sync-day-sessions is the bulk form of the "Change Sale Day Session"
 * modal: it re-points a sale at the session that actually covers its
 * created_at, and — because a closed session's closing/expected figures were
 * frozen at close time — moves the money with it.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    $this->sessionOne = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => Carbon::parse('2026-09-01 09:00'),
        'closed_at' => Carbon::parse('2026-09-01 23:00'),
        'opening_amount' => 100,
        'closing_amount' => 500,
        'expected_amount' => 500,
        'status' => 'closed',
    ]);

    $this->sessionTwo = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => Carbon::parse('2026-09-02 09:00'),
        'closed_at' => Carbon::parse('2026-09-02 23:00'),
        'opening_amount' => 100,
        'closing_amount' => 300,
        'expected_amount' => 300,
        'status' => 'closed',
    ]);

    // `total`/`grand_total`/`balance` are STORED GENERATED columns, so sales go
    // in through the query builder.
    $this->makeSale = function (string $createdAt, ?int $sessionId, float $paid = 0, string $invoiceNo = 'INV-1') {
        $saleId = DB::table('sales')->insertGetId([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'sale_day_session_id' => $sessionId,
            'account_id' => $this->world->accounts['general_customer'],
            'customer_name' => 'Walk-in Customer',
            'invoice_no' => $invoiceNo,
            'date' => Carbon::parse($createdAt)->toDateString(),
            'status' => 'completed',
            'paid' => $paid,
            'created_by' => $this->world->user->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        DB::table('sale_payments')->insert([
            'sale_id' => $saleId,
            'payment_method_id' => $this->world->cashAccountId,
            'date' => Carbon::parse($createdAt)->toDateString(),
            'amount' => $paid,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $saleId;
    };
});

it('moves a sale to the session covering its created_at and carries the dates across', function (): void {
    // Rung up on the 2nd but stamped with the 1st's session, as happens when a
    // session is left open overnight.
    $saleId = ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])->assertExitCode(0);

    $sale = Sale::withoutGlobalScopes()->find($saleId);

    expect($sale->sale_day_session_id)->toBe($this->sessionTwo->id)
        ->and((string) $sale->date)->toBe('2026-09-02')
        ->and(DB::table('sale_payments')->where('sale_id', $saleId)->value('date'))->toBe('2026-09-02');
});

it('shifts the frozen cash position of both closed sessions', function (): void {
    ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])->assertExitCode(0);

    expect((float) $this->sessionOne->fresh()->closing_amount)->toBe(300.0)
        ->and((float) $this->sessionOne->fresh()->expected_amount)->toBe(300.0)
        ->and((float) $this->sessionTwo->fresh()->closing_amount)->toBe(500.0)
        ->and((float) $this->sessionTwo->fresh()->expected_amount)->toBe(500.0);
});

it('leaves a sale alone when no session covers its created_at', function (): void {
    $saleId = ($this->makeSale)('2026-08-15 11:00:00', $this->sessionOne->id, 200);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])
        ->expectsOutputToContain('no session covers this date')
        ->assertExitCode(0);

    expect(Sale::withoutGlobalScopes()->find($saleId)->sale_day_session_id)->toBe($this->sessionOne->id)
        ->and((float) $this->sessionOne->fresh()->closing_amount)->toBe(500.0);
});

it('writes nothing on a dry run', function (): void {
    $saleId = ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);

    $this->artisan('sale:sync-day-sessions', ['--dry-run' => true])->assertExitCode(0);

    expect(Sale::withoutGlobalScopes()->find($saleId)->sale_day_session_id)->toBe($this->sessionOne->id)
        ->and((float) $this->sessionOne->fresh()->closing_amount)->toBe(500.0);
});

it('matches by calendar day when the sale falls outside every session window', function (): void {
    // Sold at 08:00, an hour before the till was opened at 09:00.
    $saleId = ($this->makeSale)('2026-09-02 08:00:00', $this->sessionOne->id, 200);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])->assertExitCode(0);

    expect(Sale::withoutGlobalScopes()->find($saleId)->sale_day_session_id)->toBe($this->sessionTwo->id);
});

it('honours --strict by refusing the same-day fallback', function (): void {
    $saleId = ($this->makeSale)('2026-09-02 08:00:00', $this->sessionOne->id, 200);

    $this->artisan('sale:sync-day-sessions', ['--force' => true, '--strict' => true])->assertExitCode(0);

    expect(Sale::withoutGlobalScopes()->find($saleId)->sale_day_session_id)->toBe($this->sessionOne->id);
});

it('ignores soft deleted sales', function (): void {
    $saleId = ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);
    DB::table('sales')->where('id', $saleId)->update(['deleted_at' => now()]);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])->assertExitCode(0);

    expect(DB::table('sales')->where('id', $saleId)->value('sale_day_session_id'))->toBe($this->sessionOne->id);
});

/**
 * The modal and the command are the same write now (ChangeDaySessionAction), so
 * these pin the two behaviours the shared action changed: a sale that never had
 * a session no longer blows up, and drafts leave the frozen till figures alone.
 */
it('moves a sale from the Change Session modal through the shared action', function (): void {
    // `permissions` carries a tenant_id, so the row has to be built with one.
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'sale.change day session',
        'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);

    $saleId = ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);

    Livewire::test(ChangeSession::class, ['table_id' => $saleId])
        ->set('selectedSessionId', $this->sessionTwo->id)
        ->call('save')
        ->assertHasNoErrors();

    $sale = Sale::withoutGlobalScopes()->find($saleId);

    expect($sale->sale_day_session_id)->toBe($this->sessionTwo->id)
        ->and((string) $sale->date)->toBe('2026-09-02')
        ->and($sale->updated_by)->toBe($this->world->user->id)
        ->and((float) $this->sessionOne->fresh()->closing_amount)->toBe(300.0)
        ->and((float) $this->sessionTwo->fresh()->closing_amount)->toBe(500.0);
});

it('assigns a sale that never had a session without touching the old till', function (): void {
    $saleId = ($this->makeSale)('2026-09-02 14:30:00', null, 200);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])->assertExitCode(0);

    expect(Sale::withoutGlobalScopes()->find($saleId)->sale_day_session_id)->toBe($this->sessionTwo->id)
        ->and((float) $this->sessionOne->fresh()->closing_amount)->toBe(500.0)
        ->and((float) $this->sessionTwo->fresh()->closing_amount)->toBe(500.0);
});

it('leaves the frozen till figures alone for a draft sale', function (): void {
    $saleId = ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);
    DB::table('sales')->where('id', $saleId)->update(['status' => 'draft']);

    $this->artisan('sale:sync-day-sessions', ['--force' => true])->assertExitCode(0);

    expect(Sale::withoutGlobalScopes()->find($saleId)->sale_day_session_id)->toBe($this->sessionTwo->id)
        ->and((float) $this->sessionOne->fresh()->closing_amount)->toBe(500.0)
        ->and((float) $this->sessionTwo->fresh()->closing_amount)->toBe(300.0);
});

it('refuses a session from another branch', function (): void {
    $branchB = $this->world->addBranch('Lusail Branch', 'LB');
    $sessionB = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $branchB->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => Carbon::parse('2026-09-02 09:00'),
        'opening_amount' => 100,
        'status' => 'open',
    ]);

    $saleId = ($this->makeSale)('2026-09-02 14:30:00', $this->sessionOne->id, 200);
    $sale = Sale::withoutGlobalScopes()->find($saleId);

    $response = (new ChangeDaySessionAction())->execute($sale, $sessionB, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('different branch')
        ->and($sale->fresh()->sale_day_session_id)->toBe($this->sessionOne->id);
});
