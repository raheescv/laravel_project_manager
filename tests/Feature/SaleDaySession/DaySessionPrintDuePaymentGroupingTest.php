<?php

use App\Actions\Sale\BuildDaySessionReportAction;
use App\Models\SaleDaySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * resources/views/sale/day-session-print.blade.php was changed to (1) hide the
 * "DUE AMOUNT DETAILS" table entirely when there is nothing due, and (2) group
 * "DUE PAYMENT RECEIVED" rows by source|reference_no so an invoice paid off in
 * several payment methods shows one header row plus one breakdown row per
 * method, instead of a flat duplicate row per (source, reference_no, method)
 * triple.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);

    $this->makeSession = fn (array $attributes = []) => SaleDaySession::create(array_merge([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(8),
        'closed_by' => $this->world->user->id,
        'closed_at' => now()->subHour(),
        'status' => 'closed',
    ], $attributes));

    // `total`/`grand_total`/`balance` are STORED GENERATED columns, so sales go
    // in through the query builder (Sale::create() also refuses a non-open
    // session, which these tests deliberately use).
    $this->makeSale = function (string $date, ?int $sessionId, float $grossAmount, string $invoiceNo) {
        return DB::table('sales')->insertGetId([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'sale_day_session_id' => $sessionId,
            'account_id' => $this->world->accounts['general_customer'],
            'customer_name' => 'Walk-in Customer',
            'invoice_no' => $invoiceNo,
            'date' => $date,
            'gross_amount' => $grossAmount,
            'status' => 'completed',
            'created_by' => $this->world->user->id,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    };

    $this->makePayment = function (int $saleId, int $paymentMethodId, string $date, float $amount): void {
        DB::table('sale_payments')->insert([
            'sale_id' => $saleId,
            'payment_method_id' => $paymentMethodId,
            'date' => $date,
            'amount' => $amount,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };
});

it('hides the due amount details table when nothing is due', function (): void {
    $sessionDate = Carbon::parse('2026-09-10 09:00:00');
    $session = ($this->makeSession)([
        'opened_at' => $sessionDate,
        'closed_at' => $sessionDate->copy()->addHours(8),
    ]);

    $saleId = ($this->makeSale)($sessionDate->toDateString(), $session->id, 100.0, 'INV-NODUE-1');
    ($this->makePayment)($saleId, $this->world->cashAccountId, $sessionDate->toDateString(), 100.0);

    $html = app(BuildDaySessionReportAction::class)->execute($session->fresh(['branch', 'opener', 'closer']))->render();

    expect($html)->not->toContain('DUE AMOUNT DETAILS')
        ->and($html)->not->toContain('DUE PAYMENT RECEIVED')
        ->and($html)->not->toContain('No due payment receipts.');
});

it('shows the due amount details table when a transaction is unpaid', function (): void {
    $sessionDate = Carbon::parse('2026-09-11 09:00:00');
    $session = ($this->makeSession)([
        'opened_at' => $sessionDate,
        'closed_at' => $sessionDate->copy()->addHours(8),
    ]);

    // No same-day payment recorded, so the full amount is due.
    ($this->makeSale)($sessionDate->toDateString(), $session->id, 100.0, 'INV-DUE-1');

    $html = app(BuildDaySessionReportAction::class)->execute($session->fresh(['branch', 'opener', 'closer']))->render();

    expect($html)->toContain('DUE AMOUNT DETAILS');
});

it('groups due payments received against the same invoice by reference instead of repeating flat rows', function (): void {
    $sessionADate = Carbon::parse('2026-09-08 09:00:00');
    $sessionBDate = Carbon::parse('2026-09-10 09:00:00');

    // The invoice belongs to an earlier, already-closed session and was left
    // fully unpaid there.
    $sessionA = ($this->makeSession)([
        'opened_at' => $sessionADate,
        'closed_at' => $sessionADate->copy()->addHours(8),
    ]);
    $saleId = ($this->makeSale)($sessionADate->toDateString(), $sessionA->id, 100.0, 'INV-CROSS-1');

    // It gets paid off during session B's open date, split across two
    // payment methods.
    $sessionB = ($this->makeSession)([
        'opened_at' => $sessionBDate,
        'closed_at' => $sessionBDate->copy()->addHours(8),
    ]);
    ($this->makePayment)($saleId, $this->world->cashAccountId, $sessionBDate->toDateString(), 60.0);
    ($this->makePayment)($saleId, $this->world->accounts['card'], $sessionBDate->toDateString(), 40.0);

    $html = app(BuildDaySessionReportAction::class)->execute($sessionB->fresh(['branch', 'opener', 'closer']))->render();

    $duePaymentSection = Str::between($html, 'DUE PAYMENT RECEIVED', 'TOTAL SUMMARY');

    // One grouped header row (Type=Sale, Reference=INV-CROSS-1) plus one
    // breakdown row per payment method (Type=Cash / Type=Card, Reference
    // repeated) — three occurrences of the reference, not two flat duplicate
    // rows that would each repeat "Sale" as the Type.
    expect(substr_count($duePaymentSection, 'INV-CROSS-1'))->toBe(3)
        ->and(substr_count($duePaymentSection, 'Cash'))->toBe(1)
        ->and(substr_count($duePaymentSection, 'Card'))->toBe(1);
});

it('hides the due payment report from the A4 pdf when nothing was received against an older bill', function (): void {
    $sessionDate = Carbon::parse('2026-09-12 09:00:00');
    $session = ($this->makeSession)([
        'opened_at' => $sessionDate,
        'closed_at' => $sessionDate->copy()->addHours(8),
    ]);

    // Paid in full, in its own session — nothing lands in Due Payment Report.
    $saleId = ($this->makeSale)($sessionDate->toDateString(), $session->id, 100.0, 'INV-PDF-NODUE-1');
    ($this->makePayment)($saleId, $this->world->cashAccountId, $sessionDate->toDateString(), 100.0);

    $html = app(BuildDaySessionReportAction::class)->executePdf($session->fresh(['branch', 'opener', 'closer']))->render();

    expect($html)->not->toContain('Due Payment Report')
        ->and($html)->not->toContain('No due payment receipts found.');
});

it('shows the due payment report on the A4 pdf when an earlier invoice is paid off', function (): void {
    $sessionADate = Carbon::parse('2026-09-13 09:00:00');
    $sessionBDate = Carbon::parse('2026-09-15 09:00:00');

    $sessionA = ($this->makeSession)([
        'opened_at' => $sessionADate,
        'closed_at' => $sessionADate->copy()->addHours(8),
    ]);
    $saleId = ($this->makeSale)($sessionADate->toDateString(), $sessionA->id, 100.0, 'INV-PDF-CROSS-1');

    $sessionB = ($this->makeSession)([
        'opened_at' => $sessionBDate,
        'closed_at' => $sessionBDate->copy()->addHours(8),
    ]);
    ($this->makePayment)($saleId, $this->world->cashAccountId, $sessionBDate->toDateString(), 100.0);

    $html = app(BuildDaySessionReportAction::class)->executePdf($sessionB->fresh(['branch', 'opener', 'closer']))->render();

    expect($html)->toContain('Due Payment Report')
        ->and($html)->toContain('INV-PDF-CROSS-1');
});

it('folds each invoice payment methods into one row on the combined thermal print', function (): void {
    $sessionDate = Carbon::parse('2026-09-16 09:00:00');
    $session = ($this->makeSession)([
        'opened_at' => $sessionDate,
        'closed_at' => $sessionDate->copy()->addHours(8),
    ]);

    $saleId = ($this->makeSale)($sessionDate->toDateString(), $session->id, 100.0, 'INV-COMBINED-1');
    ($this->makePayment)($saleId, $this->world->cashAccountId, $sessionDate->toDateString(), 60.0);
    ($this->makePayment)($saleId, $this->world->accounts['card'], $sessionDate->toDateString(), 40.0);

    $session = $session->fresh(['branch', 'opener', 'closer']);
    $action = app(BuildDaySessionReportAction::class);

    $combined = Str::between($action->execute($session, true)->render(), 'SALE TRANSACTIONS', 'TOTAL SUMMARY');
    $detailed = Str::between($action->execute($session)->render(), 'SALE TRANSACTIONS', 'TOTAL SUMMARY');

    expect(substr_count($combined, 'INV-COMBINED-1'))->toBe(1)
        ->and($combined)->not->toContain('<th align="left">Type</th>')
        ->and(substr_count($detailed, 'INV-COMBINED-1'))->toBe(3);
});

it('shows just the method name on the combined print when an invoice has one payment method', function (): void {
    $sessionDate = Carbon::parse('2026-09-17 09:00:00');
    $session = ($this->makeSession)([
        'opened_at' => $sessionDate,
        'closed_at' => $sessionDate->copy()->addHours(8),
    ]);

    $saleId = ($this->makeSale)($sessionDate->toDateString(), $session->id, 219.0, 'INV-SINGLE-1');
    ($this->makePayment)($saleId, $this->world->cashAccountId, $sessionDate->toDateString(), 219.0);

    $html = app(BuildDaySessionReportAction::class)->execute($session->fresh(['branch', 'opener', 'closer']), true)->render();
    $section = Str::between($html, 'SALE TRANSACTIONS', 'TOTAL SUMMARY');

    expect(substr_count($section, currency(219)))->toBe(1)
        ->and($section)->toContain('<strong>Cash</strong>');
});

it('folds due payments received into one row per invoice on the combined print', function (): void {
    $sessionADate = Carbon::parse('2026-09-18 09:00:00');
    $sessionBDate = Carbon::parse('2026-09-20 09:00:00');

    $sessionA = ($this->makeSession)([
        'opened_at' => $sessionADate,
        'closed_at' => $sessionADate->copy()->addHours(8),
    ]);
    $splitSaleId = ($this->makeSale)($sessionADate->toDateString(), $sessionA->id, 100.0, 'INV-DUE-SPLIT');
    $cashSaleId = ($this->makeSale)($sessionADate->toDateString(), $sessionA->id, 80.0, 'INV-DUE-CASH');

    $sessionB = ($this->makeSession)([
        'opened_at' => $sessionBDate,
        'closed_at' => $sessionBDate->copy()->addHours(8),
    ]);
    ($this->makePayment)($splitSaleId, $this->world->cashAccountId, $sessionBDate->toDateString(), 60.0);
    ($this->makePayment)($splitSaleId, $this->world->accounts['card'], $sessionBDate->toDateString(), 40.0);
    ($this->makePayment)($cashSaleId, $this->world->cashAccountId, $sessionBDate->toDateString(), 50.0);
    ($this->makePayment)($cashSaleId, $this->world->cashAccountId, $sessionBDate->toDateString(), 30.0);

    $html = app(BuildDaySessionReportAction::class)->execute($sessionB->fresh(['branch', 'opener', 'closer']), true)->render();
    $section = Str::between($html, 'DUE PAYMENT RECEIVED', 'TOTAL SUMMARY');

    expect(substr_count($section, 'INV-DUE-SPLIT'))->toBe(1)
        ->and(substr_count($section, 'INV-DUE-CASH'))->toBe(1)
        ->and($section)->not->toContain('<th align="left">Type</th>')
        ->and($section)->toContain('Card&nbsp; '.currency(40))
        ->and($section)->toContain('<strong>Cash</strong>');
});

it('serves the combined thermal print behind the day session print permission', function (): void {
    $session = ($this->makeSession)();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => 'day session.print', 'guard_name' => 'web']));

    $this->actingAs($this->world->user)
        ->get($this->world->url('/print/sale/day-session-report-combined/'.$session->id))
        ->assertOk()
        ->assertSee('SALE BILL REPORT');
});
