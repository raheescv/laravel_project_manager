<?php

use App\Livewire\RentOut\Tabs\PaymentTab;
use App\Livewire\RentOut\Tabs\RoiModal;
use App\Livewire\RentOut\Tabs\RoiPaymentModal;
use App\Livewire\RentOut\Tabs\RoiTab;
use App\Models\JournalEntry;
use App\Models\RentOutRoi;
use App\Models\RentOutTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * ROI is an expense owed to a sale agreement's buyer — the opposite of a
 * service charge. Booking it credits the customer against ROI Expense, and
 * paying it out debits the customer against the payment method.
 */

/** Both legs of a ledger row's journal, debit first. */
function roiEntries(int $transactionId): array
{
    $journalId = DB::table('rent_out_transactions')->where('id', $transactionId)->value('journal_id');

    return JournalEntry::withoutGlobalScopes()
        ->whereNull('deleted_at')
        ->where('journal_id', $journalId)
        ->orderByDesc('debit')
        ->get()
        ->all();
}

function roiExpenseAccountId(int $tenantId): ?int
{
    return DB::table('accounts')->where('tenant_id', $tenantId)->where('slug', 'roi_expense')->where('is_locked', 1)->value('id');
}

beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    session(['branch_id' => 1]);

    foreach (['rent out roi.create', 'rent out roi.payment', 'rent out roi.delete'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $account = fn (string $name, string $type) => DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => $name.' '.Str::random(6), 'account_type' => $type,
    ]);

    $this->customerId = $account('Unit Buyer', 'asset');
    $this->cashId = $account('Cash', 'asset');

    $groupId = DB::table('property_groups')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'name' => 'Group '.Str::random(6),
    ]);
    $buildingId = DB::table('property_buildings')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6),
    ]);
    $typeId = DB::table('property_types')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => '1 Bedroom '.Str::random(6),
    ]);
    $propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'number' => '203',
        'ownership' => 'Owner',
    ]);

    $this->makeRentOut = fn (string $agreementType) => DB::table('rent_outs')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_id' => $propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'account_id' => $this->customerId,
        'agreement_type' => $agreementType,
        'rent' => 1629740,
        'start_date' => '2026-09-21',
        'end_date' => '2031-12-31',
    ]);

    $this->rentOutId = ($this->makeRentOut)('lease');
});

/** Opens the ROI modal for the 21/09/2026 – 31/12/2026 period (102 days → 3 months). */
function openRoiModal(int $rentOutId)
{
    return Livewire::test(RoiModal::class)
        ->call('openModal', $rentOutId)
        ->set('date', '2026-10-28')
        ->set('startDate', '2026-09-21')
        ->set('endDate', '2026-12-31');
}

it('multiplies the per month ROI by the months in the period', function () {
    openRoiModal($this->rentOutId)
        ->set('perMonthRoi', '8148.70')
        ->assertSet('salePrice', 1629740.0)
        ->assertSet('noOfMonths', 3)
        ->assertSet('amount', 24446.1)
        ->assertSet('description', 'ROI for the period '.systemDate('2026-09-21').' to '.systemDate('2026-12-31'))
        ->assertSee('6.00% per year of sale price');
});

it('books ROI as an expense owed to the customer', function () {
    openRoiModal($this->rentOutId)
        ->set('perMonthRoi', '8148.70')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $roi = RentOutRoi::where('rent_out_id', $this->rentOutId)->sole();
    expect((float) $roi->amount)->toBe(24446.1)
        ->and((float) $roi->per_month_roi)->toBe(8148.7)
        ->and($roi->no_of_months)->toBe(3);

    $expenseId = roiExpenseAccountId($this->tenantId);
    expect($expenseId)->not->toBeNull();

    $row = DB::table('rent_out_transactions')->where('model', 'RentOutRoi')->where('model_id', $roi->id)->sole();
    expect($row->source)->toBe(RentOutRoi::SOURCE)
        ->and((float) $row->credit)->toBe(24446.1)
        ->and((float) $row->debit)->toBe(0.0)
        ->and((int) $row->account_id)->toBe($this->customerId);

    [$debit, $credit] = roiEntries($row->id);
    expect((int) $debit->account_id)->toBe($expenseId)
        ->and((float) $debit->debit)->toBe(24446.1)
        ->and((int) $debit->counter_account_id)->toBe($this->customerId)
        ->and((int) $credit->account_id)->toBe($this->customerId)
        ->and((float) $credit->credit)->toBe(24446.1)
        ->and((int) $credit->counter_account_id)->toBe($expenseId);
});

it('requires a per month ROI', function () {
    openRoiModal($this->rentOutId)
        ->call('save')
        ->assertHasErrors(['perMonthRoi' => 'required']);

    expect(RentOutRoi::count())->toBe(0);
});

it('refuses ROI on a rental agreement', function () {
    $rentalId = ($this->makeRentOut)('rental');

    openRoiModal($rentalId)
        ->set('perMonthRoi', '1000')
        ->call('save')
        ->assertDispatched('error');

    expect(RentOutRoi::count())->toBe(0);
});

it('forbids adding ROI without the permission', function () {
    $this->actingAs(User::factory()->create(['tenant_id' => $this->tenantId]));

    openRoiModal($this->rentOutId)
        ->set('perMonthRoi', '1000')
        ->call('save')
        ->assertForbidden();
});

it('pays ROI out of the chosen payment method, up to the balance', function () {
    openRoiModal($this->rentOutId)->set('perMonthRoi', '1000')->call('save');

    Livewire::test(RoiPaymentModal::class)
        ->call('openModal', $this->rentOutId)
        ->assertSet('balance', 3000.0)
        ->assertSet('form.amount', 3000.0)
        ->set('form.amount', 3500)
        ->set('form.account_id', $this->cashId)
        ->call('save')
        ->assertHasErrors(['form.amount' => 'max'])
        ->set('form.amount', 2000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $row = DB::table('rent_out_transactions')->where('source', RentOutRoi::PAYMENT_SOURCE)->sole();
    expect((float) $row->debit)->toBe(2000.0)
        ->and((int) $row->account_id)->toBe($this->cashId);

    [$debit, $credit] = roiEntries($row->id);
    expect((int) $debit->account_id)->toBe($this->customerId)
        ->and((float) $debit->debit)->toBe(2000.0)
        ->and((int) $credit->account_id)->toBe($this->cashId)
        ->and((float) $credit->credit)->toBe(2000.0);

    expect(RentOutRoi::totalsFor($this->rentOutId))->toBe(['accrued' => 3000.0, 'paid' => 2000.0, 'balance' => 1000.0]);
});

it('keeps ROI out of the payments received list', function () {
    openRoiModal($this->rentOutId)->set('perMonthRoi', '1000')->call('save');

    Livewire::test(PaymentTab::class, ['rentOutId' => $this->rentOutId])
        ->assertViewHas('payments', fn ($payments) => $payments->isEmpty());
});

it('only deletes a paid ROI once its payment is removed, taking the journals with it', function () {
    openRoiModal($this->rentOutId)->set('perMonthRoi', '1000')->call('save');
    Livewire::test(RoiPaymentModal::class)
        ->call('openModal', $this->rentOutId)
        ->set('form.account_id', $this->cashId)
        ->call('save');

    $roi = RentOutRoi::sole();
    $payment = DB::table('rent_out_transactions')->where('source', RentOutRoi::PAYMENT_SOURCE)->sole();
    $journalIds = DB::table('rent_out_transactions')->where('rent_out_id', $this->rentOutId)->pluck('journal_id');

    Livewire::test(RoiTab::class, ['rentOutId' => $this->rentOutId])
        ->call('deleteRoi', $roi->id)
        ->assertDispatched('error');
    expect(RentOutRoi::count())->toBe(1);

    Livewire::test(RoiTab::class, ['rentOutId' => $this->rentOutId])
        ->call('deletePayment', $payment->id)
        ->call('deleteRoi', $roi->id)
        ->assertDispatched('success');

    expect(RentOutRoi::count())->toBe(0)
        ->and(DB::table('rent_out_transactions')->where('rent_out_id', $this->rentOutId)->whereNull('deleted_at')->count())->toBe(0)
        ->and(JournalEntry::withoutGlobalScopes()->whereIn('journal_id', $journalIds)->whereNull('deleted_at')->count())->toBe(0);
});

it('lists ROI as credits and payouts as debits with a running balance payable', function () {
    openRoiModal($this->rentOutId)->set('perMonthRoi', '1000')->call('save');
    Livewire::test(RoiPaymentModal::class)
        ->call('openModal', $this->rentOutId)
        ->set('form.amount', 2000)
        ->set('form.account_id', $this->cashId)
        ->call('save');

    Livewire::test(RoiTab::class, ['rentOutId' => $this->rentOutId])
        ->assertViewHas('entries', fn ($entries) => $entries->map(fn (array $entry) => [
            (float) $entry['transaction']->credit,
            (float) $entry['transaction']->debit,
            $entry['balance'],
        ])->all() === [[3000.0, 0.0, 3000.0], [0.0, 2000.0, 1000.0]])
        ->assertSee('Total ROI (Credit)')
        ->assertSee('Paid (Debit)');
});

it('prints an ROI row as the ROI invoice and a payout as the ROI payment voucher', function () {
    openRoiModal($this->rentOutId)->set('perMonthRoi', '1000')->call('save');
    Livewire::test(RoiPaymentModal::class)
        ->call('openModal', $this->rentOutId)
        ->set('form.account_id', $this->cashId)
        ->call('save');

    $render = function (string $source): string {
        $payment = RentOutTransaction::with(['account', 'rentOut.customer', 'rentOut.property', 'rentOut.building'])
            ->where('source', $source)
            ->sole();

        return view('print.rentout.voucher', [
            'payment' => $payment,
            'rentOut' => $payment->rentOut,
            'roi' => $source === RentOutRoi::SOURCE ? RentOutRoi::find($payment->model_id) : null,
            'companyName' => 'Acme', 'companyAddress' => '', 'companyPhone' => '', 'companyEmail' => '', 'companyLogo' => null,
        ])->render();
    };

    expect($render(RentOutRoi::SOURCE))
        ->toContain('ROI Invoice')
        ->toContain('ROI Payable')
        ->toContain('Payable To')
        ->toContain('21-09-2026 to 31-12-2026')
        ->toContain('&times; 3 months')
        ->not->toContain('Payment Mode');

    expect($render(RentOutRoi::PAYMENT_SOURCE))
        ->toContain('ROI Payment Voucher')
        ->toContain('Amount Paid')
        ->toContain('Payment Mode');

    $roiRowId = RentOutTransaction::where('source', RentOutRoi::SOURCE)->value('id');
    Livewire::test(RoiTab::class, ['rentOutId' => $this->rentOutId])
        ->call('printDocument', $roiRowId)
        ->assertDispatched('open-receipt-tab', url: route('print::rentout::payment-voucher', $roiRowId));
});
