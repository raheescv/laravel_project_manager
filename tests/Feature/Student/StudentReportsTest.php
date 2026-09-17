<?php

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Actions\Student\ManualEntryAction;
use App\Livewire\Report\Student\QPayRechargeReport;
use App\Livewire\Report\Student\WalletReport;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\QpayTransaction;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * The two school reports: every card balance, and every QPay recharge.
 *
 * The wallet report is read from the ledger, so its "on cards now" total must
 * equal what the books say the school owes families — that is the check worth
 * pinning, not the markup.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100, price: 30);
    StudentWorld::enableSchool($this->world);
    foreach (['report.student wallet', 'report.student recharge'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id, 'name' => $name, 'guard_name' => 'web',
        ]));
    }
    $this->actingAs($this->world->user);

    $this->sara = StudentWorld::enrol($this->world);
    $this->omar = StudentWorld::enrol($this->world, ['name' => 'Omar Ahmed', 'grade' => 'Grade 2', 'section' => 'A', 'card_uid' => '0A0B0C0D', 'guardians' => []]);

    // Omar's 30 purchase leaves him 10 overdrawn, which the school's limit allows.
    StudentWorld::setOverdraft($this->world, 30);
    StudentWorld::topUp($this->world, $this->sara, 150);
    StudentWorld::topUp($this->world, $this->omar, 20);
    foreach ([$this->sara, $this->omar] as $student) {
        $response = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $student->id, 30, card: 30), $this->world->user->id);
        expect($response['success'])->toBeTrue($response['message']);
    }
});

it('lists every student with opening, movement and closing balance', function (): void {
    Livewire::test(WalletReport::class)
        ->assertOk()
        ->assertSee('Sara Ahmed')
        ->assertSee('Omar Ahmed')
        ->assertSee('120.00')  // Sara: 150 in, 30 spent
        ->assertSee('-10.00'); // Omar: 20 in, 30 spent — overdrawn
});

it('totals to what the books say the school holds for families', function (): void {
    $ledger = -1 * JournalEntry::whereIn('account_id', Account::student()->pluck('id'))
        ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
        ->value('balance');

    $totals = Livewire::test(WalletReport::class)->viewData('totals');

    expect(round((float) $totals->closing, 2))->toBe(round((float) $ledger, 2))
        ->and(round((float) $totals->closing, 2))->toBe(110.0)
        ->and(round((float) $totals->period_in, 2))->toBe(170.0)
        ->and(round((float) $totals->period_out, 2))->toBe(60.0)
        ->and((int) $totals->students)->toBe(2)
        ->and((int) $totals->overdrawn_students)->toBe(1)
        ->and(round((float) $totals->overdrawn, 2))->toBe(-10.0);
});

it('separates the opening balance from the period, and filters', function (): void {
    // Move everything so far into the past, then add this month's movement.
    JournalEntry::query()->update(['date' => now()->subMonth()->toDateString()]);
    (new ManualEntryAction())->execute($this->sara->id, 40, $this->world->cashAccountId, 'add', 'Cash at the office', null, $this->world->user->id);

    $report = Livewire::test(WalletReport::class)->set('from_date', now()->startOfMonth()->toDateString());
    $row = collect($report->viewData('rows')->items())->firstWhere('id', $this->sara->id);

    expect(round((float) $row->opening_balance, 2))->toBe(120.0)
        ->and(round((float) $row->period_in, 2))->toBe(40.0)
        ->and(round((float) $row->closing_balance, 2))->toBe(160.0);

    // Overdrawn-only and the class filter narrow the list.
    expect(collect(Livewire::test(WalletReport::class)->set('overdrawn_only', true)->viewData('rows')->items())->pluck('name')->all())
        ->toBe(['Omar Ahmed']);
    expect(collect(Livewire::test(WalletReport::class)->set('grade', 'Grade 2')->viewData('rows')->items())->pluck('name')->all())
        ->toBe(['Omar Ahmed']);
});

it('reports QPay recharges with the money actually collected', function (): void {
    $guardian = $this->sara->guardians->first();
    QpayTransaction::create(['type' => 'payment', 'pun' => 'PUNSUCCESS0000000001', 'account_id' => $this->sara->id, 'guardian_id' => $guardian->id, 'amount' => 150, 'status' => 'success', 'gateway_status' => '0000', 'masked_card' => '421537******3243', 'completed_at' => now()]);
    QpayTransaction::create(['type' => 'payment', 'pun' => 'PUNFAILED00000000001', 'account_id' => $this->sara->id, 'guardian_id' => $guardian->id, 'amount' => 90, 'status' => 'failed', 'gateway_status' => '3000', 'gateway_status_message' => 'Payment Failed.']);
    QpayTransaction::create(['type' => 'payment', 'pun' => 'PUNPENDING0000000001', 'account_id' => $this->omar->id, 'amount' => 40, 'status' => 'pending']);
    QpayTransaction::create(['type' => 'refund', 'pun' => 'PUNREFUND00000000001', 'original_pun' => 'PUNSUCCESS0000000001', 'account_id' => $this->sara->id, 'amount' => 150, 'status' => 'success']);

    $report = Livewire::test(QPayRechargeReport::class)->assertOk()->assertSee('PUNSUCCESS0000000001');
    $totals = $report->viewData('totals');

    expect(round((float) $totals['collected'], 2))->toBe(150.0)
        ->and(round((float) $totals['refunded'], 2))->toBe(150.0)
        ->and($totals['payments'])->toBe(1)
        ->and($totals['pending'])->toBe(1)
        ->and($totals['failed'])->toBe(1);

    // Filters narrow to one row each.
    expect(Livewire::test(QPayRechargeReport::class)->set('status', 'pending')->viewData('rows')->count())->toBe(1);
    expect(Livewire::test(QPayRechargeReport::class)->set('search', 'Omar')->viewData('rows')->count())->toBe(1);
    expect(Livewire::test(QPayRechargeReport::class)->set('type', 'refund')->viewData('rows')->count())->toBe(1);
});

it('exports both reports', function (): void {
    $this->freezeTime();
    Excel::fake();

    Livewire::test(WalletReport::class)->call('export');
    Excel::assertDownloaded('student_wallet_'.now()->timestamp.'.xlsx');

    Livewire::test(QPayRechargeReport::class)->call('export');
    Excel::assertDownloaded('qpay_recharges_'.now()->timestamp.'.xlsx');
});

it('opens both report pages, and hides them outside the School module', function (): void {
    $this->get($this->world->url('/student/report/wallet'))->assertOk()->assertSee('Student Wallet Report');
    $this->get($this->world->url('/student/report/recharges'))->assertOk()->assertSee('QPay Recharge Report');

    \App\Models\Configuration::updateOrCreate(
        ['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'],
        ['value' => 'POS Module'],
    );

    $this->get($this->world->url('/student/report/wallet'))->assertNotFound();
    $this->get($this->world->url('/student/report/recharges'))->assertNotFound();
});
