<?php

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Actions\Student\ManualEntryAction;
use App\Livewire\Report\Student\QPayRechargeReport;
use App\Livewire\Report\Student\WalletReport;
use App\Models\Account;
use App\Models\ApiLog;
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
            'name' => $name, 'guard_name' => 'web',
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

it('sets the period from the rail shortcuts and resets every filter', function (): void {
    $report = Livewire::test(WalletReport::class)->call('setRange', 'last_month');

    expect($report->get('from_date'))->toBe(now()->subMonthNoOverflow()->startOfMonth()->toDateString())
        ->and($report->get('to_date'))->toBe(now()->subMonthNoOverflow()->endOfMonth()->toDateString())
        ->and($report->instance()->currentRange())->toBe('last_month');

    // An unknown key is ignored rather than silently widening the report.
    $report->call('setRange', 'all_time');
    expect($report->get('from_date'))->toBe(now()->subMonthNoOverflow()->startOfMonth()->toDateString());

    $report->set('search', 'Omar')->set('grade', 'Grade 2')->set('overdrawn_only', true)->set('status', '')
        ->call('resetFilters');

    expect($report->get('search'))->toBe('')
        ->and($report->get('grade'))->toBe('')
        ->and($report->get('overdrawn_only'))->toBeFalse()
        ->and($report->get('status'))->toBe('active')
        ->and($report->get('from_date'))->toBe(now()->startOfMonth()->toDateString())
        ->and($report->instance()->currentRange())->toBe('this_month');
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
    expect(Livewire::test(QPayRechargeReport::class)->call('toggleStatus', 'pending')->viewData('rows')->count())->toBe(1);
    expect(Livewire::test(QPayRechargeReport::class)->call('toggleStatus', 'pending')->call('toggleStatus', 'failed')->viewData('rows')->count())->toBe(2);
    expect(Livewire::test(QPayRechargeReport::class)->set('search', 'Omar')->viewData('rows')->count())->toBe(1);
    expect(Livewire::test(QPayRechargeReport::class)->set('type', 'refund')->viewData('rows')->count())->toBe(1);
});

it('filters the recharge report from its summary cards, keeping every card total', function (): void {
    QpayTransaction::create(['type' => 'payment', 'pun' => 'PUNSUCCESS0000000001', 'account_id' => $this->sara->id, 'amount' => 150, 'status' => 'success']);
    QpayTransaction::create(['type' => 'payment', 'pun' => 'PUNFAILED00000000001', 'account_id' => $this->sara->id, 'amount' => 90, 'status' => 'failed']);
    QpayTransaction::create(['type' => 'payment', 'pun' => 'PUNPENDING0000000001', 'account_id' => $this->omar->id, 'amount' => 40, 'status' => 'pending']);
    QpayTransaction::create(['type' => 'refund', 'pun' => 'PUNREFUND00000000001', 'account_id' => $this->sara->id, 'amount' => 150, 'status' => 'success']);
    QpayTransaction::create(['type' => 'refund', 'pun' => 'PUNREFUNDFAILED00001', 'account_id' => $this->sara->id, 'amount' => 150, 'status' => 'failed']);

    $report = Livewire::test(QPayRechargeReport::class)->call('filterCard', 'failed');
    expect($report->get('status'))->toBe(['failed'])
        ->and($report->viewData('activeCard'))->toBe('failed')
        ->and($report->viewData('rows')->pluck('pun')->sort()->values()->all())->toBe(['PUNFAILED00000000001', 'PUNREFUNDFAILED00001'])
        ->and($report->viewData('totals')['pending'])->toBe(1)
        ->and(round((float) $report->viewData('totals')['collected'], 2))->toBe(150.0);

    $report->call('filterCard', 'payments');
    expect($report->viewData('rows')->pluck('pun')->all())->toBe(['PUNSUCCESS0000000001']);

    $report->call('filterCard', 'refunded');
    expect($report->get('type'))->toBe('refund')
        ->and($report->viewData('activeCard'))->toBe('refunded')
        ->and($report->viewData('rows')->pluck('pun')->all())->toBe(['PUNREFUND00000000001']);

    $report->call('filterCard', 'refunded');
    expect($report->get('status'))->toBe([])
        ->and($report->get('type'))->toBe('')
        ->and($report->viewData('rows')->count())->toBe(5);
});

it('opens a recharge with its gateway log and a plain-language explanation', function (): void {
    $guardian = $this->sara->guardians->first();
    $payment = QpayTransaction::create(['type' => 'payment', 'gateway' => 'qpay', 'pun' => 'PUNDETAIL00000000001', 'account_id' => $this->sara->id, 'guardian_id' => $guardian->id, 'amount' => 90, 'status' => 'failed', 'gateway_status' => '3000', 'gateway_status_message' => 'Payment Failed.', 'failure_reason' => 'Payment Failed.']);
    $refund = QpayTransaction::create(['type' => 'refund', 'gateway' => 'qpay', 'pun' => 'PUNDETAILREFUND00001', 'original_pun' => 'PUNDETAIL00000000001', 'account_id' => $this->sara->id, 'amount' => 90, 'status' => 'failed']);
    ApiLog::create(['endpoint' => 'https://qpay.test/pay', 'method' => 'POST', 'service_name' => 'QPay Payment', 'status' => 'failed', 'request' => json_encode(json_encode(['PUN' => 'PUNDETAIL00000000001', 'BankID' => 'QPAYPG02'])), 'response' => json_encode(['Status' => '3000', 'StatusMessage' => 'Payment Failed.']), 'description' => '3000: Payment Failed.']);
    ApiLog::create(['endpoint' => 'https://qpay.test/inquiry', 'method' => 'POST', 'service_name' => 'QPay Inquiry', 'status' => 'success', 'request' => json_encode(['OriginalPUN' => 'PUNDETAILREFUND00001']), 'response' => json_encode(['Status' => '0000'])]);
    ApiLog::create(['endpoint' => 'https://qpay.test/pay', 'method' => 'POST', 'service_name' => 'QPay Payment', 'status' => 'success', 'request' => json_encode(['PUN' => 'PUNOTHER000000000001'])]);

    $report = Livewire::test(QPayRechargeReport::class)->call('showDetails', $payment->id)
        ->assertSee('Payment failed — no money taken')
        ->assertSee('Nothing was booked in the accounts')
        ->assertSee('PUNDETAIL00000000001')
        ->assertDontSee('PUNOTHER000000000001');

    expect(\App\Support\Payment\QPayRechargeExplanation::decoded($report->viewData('detailLogs')->first()->request))
        ->toBe(['PUN' => 'PUNDETAIL00000000001', 'BankID' => 'QPAYPG02']);

    expect($report->viewData('detailLogs')->pluck('service_name')->all())->toBe(['QPay Payment', 'QPay Inquiry'])
        ->and($report->viewData('detailRelated')->pluck('id')->all())->toBe([$refund->id]);

    $report->call('closeDetails')->assertSet('detailId', null)->assertDontSee('Payment failed — no money taken');
});

it('opens a recharge straight from its link', function (): void {
    $payment = QpayTransaction::create(['type' => 'payment', 'gateway' => 'qpay', 'pun' => 'PUNLINK0000000000001', 'currency_code' => '634', 'account_id' => $this->sara->id, 'amount' => 100, 'status' => 'pending']);

    Livewire::withQueryParams(['txn' => $payment->id])->test(QPayRechargeReport::class)
        ->assertSet('detailId', $payment->id)
        ->assertSee('Waiting for QPay')
        ->assertSee('QAR')
        ->assertSee('Copy link');

    $this->get($this->world->url('/student/report/recharges?txn='.$payment->id))->assertOk()->assertSee('Waiting for QPay');

    // A blank or mangled link just shows the report.
    foreach (['', 'abc', '-4', '99999999'] as $txn) {
        $this->get($this->world->url('/student/report/recharges?txn='.$txn))->assertOk()->assertDontSee('Waiting for QPay');
    }
});

it('explains a credit card top-up from the Mastercard Gateway answers', function (): void {
    $payment = QpayTransaction::create(['type' => 'payment', 'gateway' => 'mpgs', 'pun' => 'PUNMPGS0000000000001', 'account_id' => $this->sara->id, 'amount' => 50, 'status' => 'failed', 'card_brand' => 'MASTERCARD', 'masked_card' => '512345xxxxxx0008', 'payload' => ['gateway_code' => 'INSUFFICIENT_FUNDS', 'authentication_status' => 'AUTHENTICATION_SUCCESSFUL']]);
    ApiLog::create(['endpoint' => 'https://mpgs.test/api/rest/version/100/merchant/X/order/PUNMPGS0000000000001', 'method' => 'GET', 'service_name' => 'MPGS Retrieve Order', 'status' => 'success', 'response' => json_encode(['status' => 'FAILED', 'transaction' => [['transaction' => ['type' => 'PAYMENT'], 'response' => ['gatewayCode' => 'INSUFFICIENT_FUNDS']]]])]);

    Livewire::test(QPayRechargeReport::class)->call('showDetails', $payment->id)
        ->assertSee('Mastercard ····0008')
        ->assertSee('not enough funds on the card')
        ->assertSee('cardholder verified by their bank')
        ->assertSee('Order failed');
});

it('resets the recharge report filters back to this month', function (): void {
    $report = Livewire::test(QPayRechargeReport::class)
        ->set('search', 'Omar')->call('toggleStatus', 'pending')->set('type', 'refund')->set('gateway', 'mpgs')
        ->call('setRange', 'last_30')
        ->call('resetFilters');

    expect($report->get('search'))->toBe('')
        ->and($report->get('status'))->toBe([])
        ->and($report->get('type'))->toBe('')
        ->and($report->get('gateway'))->toBe('')
        ->and($report->get('from_date'))->toBe(now()->startOfMonth()->toDateString())
        ->and($report->instance()->currentRange())->toBe('this_month');
});

it('exports both reports', function (): void {
    $this->freezeTime();
    Excel::fake();

    Livewire::test(WalletReport::class)->call('export');
    Excel::assertDownloaded('student_wallet_'.now()->timestamp.'.xlsx');

    Livewire::test(QPayRechargeReport::class)->call('export');
    Excel::assertDownloaded('online_recharges_'.now()->timestamp.'.xlsx');
});

it('opens both report pages, and hides them outside the School module', function (): void {
    $this->get($this->world->url('/student/report/wallet'))->assertOk()->assertSee('Student Wallet Report');
    $this->get($this->world->url('/student/report/recharges'))->assertOk()->assertSee('Online Recharge Report');

    \App\Models\Configuration::updateOrCreate(
        ['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'],
        ['value' => 'POS Module'],
    );

    $this->get($this->world->url('/student/report/wallet'))->assertNotFound();
    $this->get($this->world->url('/student/report/recharges'))->assertNotFound();
});
