<?php

use App\Actions\RentOut\Commission\CreateAction;
use App\Enums\RentOut\CommissionStatus;
use App\Exports\RentOut\CommissionExport;
use App\Livewire\RentOut\CommissionTable;
use App\Livewire\RentOut\Report\CommissionReport;
use App\Livewire\RentOut\Tabs\CommissionModal;
use App\Livewire\RentOut\Tabs\CommissionPayModal;
use App\Livewire\RentOut\Tabs\CommissionTab;
use App\Livewire\RentOut\View;
use App\Models\JournalEntry;
use App\Models\RentOutCommission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Agent commission on a sale agreement: entered per deal (percentage of the
 * contract or a fixed amount), approved CEO → Owner (optional) → Finance, and
 * only finance approval and payment touch the books.
 */
function commissionJournalEntries(?int $journalId): array
{
    return JournalEntry::withoutGlobalScopes()
        ->whereNull('deleted_at')
        ->where('journal_id', $journalId)
        ->orderByDesc('debit')
        ->get()
        ->all();
}

function lockedAccount(int $tenantId, string $slug): ?int
{
    return DB::table('accounts')->where('tenant_id', $tenantId)->where('slug', $slug)->where('is_locked', 1)->value('id');
}

beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->user = $user;
    $this->tenantId = $user->tenant_id;
    session(['branch_id' => 1]);

    foreach (['view', 'create', 'edit', 'delete', 'ceo approve', 'owner approve', 'finance approve', 'pay'] as $action) {
        $user->givePermissionTo(Permission::findOrCreate('rent out commission.'.$action, 'web'));
    }

    $this->agent = User::factory()->create(['tenant_id' => $this->tenantId, 'type' => 'employee', 'name' => 'Sameer Agent']);

    $account = fn (string $name, string $type) => DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => $name.' '.Str::random(6), 'account_type' => $type,
    ]);
    $this->cashId = $account('Cash', 'asset');

    $groupId = DB::table('property_groups')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => 1, 'name' => 'Group '.Str::random(6)]);
    $buildingId = DB::table('property_buildings')->insertGetId(['tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6)]);
    $typeId = DB::table('property_types')->insertGetId(['tenant_id' => $this->tenantId, 'name' => 'Flat '.Str::random(6)]);
    $propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId,
        'property_building_id' => $buildingId, 'property_type_id' => $typeId, 'number' => '1203', 'ownership' => 'Owner',
    ]);

    $this->rentOutId = DB::table('rent_outs')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_id' => $propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $buildingId,
        'property_type_id' => $typeId,
        'account_id' => $account('Buyer', 'asset'),
        'salesman_id' => $this->agent->id,
        'agreement_type' => 'lease',
        'rent' => 60000,
        'start_date' => '2026-07-01',
        'end_date' => '2031-06-30',
    ]);
});

function addCommission(int $rentOutId, array $overrides = [])
{
    $modal = Livewire::test(CommissionModal::class)->call('openModal', $rentOutId);
    foreach ($overrides as $key => $value) {
        $modal->set('form.'.$key, $value);
    }

    return $modal->call('save');
}

it('uses the agreement salesman as the agent and the sale price as the contract', function () {
    Livewire::test(CommissionModal::class)
        ->call('openModal', $this->rentOutId)
        ->assertSet('salesmanName', 'Sameer Agent')
        ->assertSet('form.contract_amount', 60000.0)
        ->set('form.commission_percentage', 5)
        ->assertSee('3,000.00');
});

it('saves a percentage commission as a draft without touching the books', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5])->assertHasNoErrors()->assertDispatched('success');

    $commission = RentOutCommission::where('rent_out_id', $this->rentOutId)->sole();
    expect($commission->status)->toBe(CommissionStatus::Draft)
        ->and((float) $commission->amount)->toBe(3000.0)
        ->and($commission->agent_id)->toBe($this->agent->id)
        ->and($commission->journal_id)->toBeNull();
});

it('saves a fixed commission as the amount entered', function () {
    addCommission($this->rentOutId, ['commission_type' => 'fixed', 'amount' => 1000])->assertHasNoErrors();

    $commission = RentOutCommission::sole();
    expect($commission->commission_type)->toBe('fixed')
        ->and((float) $commission->commission_percentage)->toBe(0.0)
        ->and((float) $commission->amount)->toBe(1000.0);
});

it('ignores any other agent and always records the agreement salesman', function () {
    $other = User::factory()->create(['tenant_id' => $this->tenantId, 'type' => 'employee']);

    $response = (new CreateAction())->execute([
        'rent_out_id' => $this->rentOutId, 'agent_id' => $other->id, 'commission_type' => 'fixed', 'amount' => 500,
    ], $this->user->id);

    expect($response['success'])->toBeTrue()
        ->and(RentOutCommission::sole()->agent_id)->toBe($this->agent->id);
});

it('refuses a commission when the agreement has no salesman', function () {
    DB::table('rent_outs')->where('id', $this->rentOutId)->update(['salesman_id' => null]);

    Livewire::test(CommissionModal::class)
        ->call('openModal', $this->rentOutId)
        ->assertDispatched('error', message: 'Set a salesman on this agreement before adding a commission.')
        ->assertNotDispatched('ToggleCommissionModal');

    expect(RentOutCommission::count())->toBe(0);
});

it('requires a rate for a percentage commission', function () {
    addCommission($this->rentOutId)->assertHasErrors(['form.commission_percentage' => 'required']);

    expect(RentOutCommission::count())->toBe(0);
});

it('walks the approval chain and posts the expense on finance approval', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();

    $tab = Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId]);
    $tab->call('submit', $commission->id)->assertDispatched('success');
    $tab->call('approve', $commission->id, 'ceo');
    $tab->call('approve', $commission->id, 'owner');
    expect($commission->refresh()->status)->toBe(CommissionStatus::OwnerApproved)
        ->and($commission->journal_id)->toBeNull();

    $tab->call('approve', $commission->id, 'finance')->assertDispatched('success');
    $commission->refresh();
    expect($commission->status)->toBe(CommissionStatus::FinanceApproved)
        ->and($commission->invoice_no)->toBe('COM-'.str_pad((string) $commission->id, 5, '0', STR_PAD_LEFT));

    $expenseId = lockedAccount($this->tenantId, 'commission_expense');
    $payableId = lockedAccount($this->tenantId, 'commission_payable');
    [$debit, $credit] = commissionJournalEntries($commission->journal_id);
    expect((int) $debit->account_id)->toBe($expenseId)
        ->and((float) $debit->debit)->toBe(3000.0)
        ->and((int) $debit->counter_account_id)->toBe($payableId)
        ->and((int) $credit->account_id)->toBe($payableId)
        ->and((float) $credit->credit)->toBe(3000.0)
        ->and($debit->model)->toBe('RentOutCommission');
});

it('lets finance approve straight after the CEO, skipping the optional owner step', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();

    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('submit', $commission->id)
        ->call('approve', $commission->id, 'ceo')
        ->call('approve', $commission->id, 'finance');

    expect($commission->refresh()->status)->toBe(CommissionStatus::FinanceApproved);
});

it('refuses to skip the CEO approval', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();

    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('submit', $commission->id)
        ->call('approve', $commission->id, 'finance')
        ->assertDispatched('error');

    expect($commission->refresh()->status)->toBe(CommissionStatus::Submitted)
        ->and($commission->journal_id)->toBeNull();
});

it('pays an approved commission from the chosen payment method and can cancel it', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();
    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('submit', $commission->id)
        ->call('approve', $commission->id, 'ceo')
        ->call('approve', $commission->id, 'finance');

    Livewire::test(CommissionPayModal::class)
        ->call('openModal', $commission->id)
        ->set('form.payment_account_id', $this->cashId)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $commission->refresh();
    expect($commission->status)->toBe(CommissionStatus::Paid);
    $payableId = lockedAccount($this->tenantId, 'commission_payable');
    [$debit, $credit] = commissionJournalEntries($commission->payment_journal_id);
    expect((int) $debit->account_id)->toBe($payableId)
        ->and((float) $debit->debit)->toBe(3000.0)
        ->and((int) $credit->account_id)->toBe($this->cashId)
        ->and((float) $credit->credit)->toBe(3000.0);

    $paymentJournalId = $commission->payment_journal_id;
    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])->call('cancelPayment', $commission->id);

    expect($commission->refresh()->status)->toBe(CommissionStatus::FinanceApproved)
        ->and(commissionJournalEntries($paymentJournalId))->toBe([])
        ->and(commissionJournalEntries($commission->journal_id))->toHaveCount(2);
});

it('sends a rejected commission back for editing, then resubmits as draft', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();

    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('submit', $commission->id)
        ->call('reject', $commission->id, 'Rate too high');
    expect($commission->refresh()->status)->toBe(CommissionStatus::Rejected)
        ->and($commission->rejection_reason)->toBe('Rate too high');

    Livewire::test(CommissionModal::class)
        ->call('openModal', $this->rentOutId, $commission->id)
        ->set('form.commission_percentage', 4)
        ->call('save')
        ->assertHasNoErrors();

    $commission->refresh();
    expect($commission->status)->toBe(CommissionStatus::Draft)
        ->and((float) $commission->amount)->toBe(2400.0)
        ->and($commission->rejection_reason)->toBeNull();
});

it('reopens an unpaid finance-approved commission and removes its journal', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();
    $tab = Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId]);
    $tab->call('submit', $commission->id)->call('approve', $commission->id, 'ceo')->call('approve', $commission->id, 'finance');
    $journalId = $commission->refresh()->journal_id;

    $tab->call('reopen', $commission->id);

    expect($commission->refresh()->status)->toBe(CommissionStatus::Draft)
        ->and($commission->journal_id)->toBeNull()
        ->and(commissionJournalEntries($journalId))->toBe([]);
});

it('cannot delete a commission once approval has started', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();

    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('submit', $commission->id)
        ->call('delete', $commission->id)
        ->assertDispatched('error');

    expect(RentOutCommission::count())->toBe(1);
});

it('forbids approving without the step permission', function () {
    addCommission($this->rentOutId, ['commission_percentage' => 5]);
    $commission = RentOutCommission::sole();
    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])->call('submit', $commission->id);

    $this->user->revokePermissionTo('rent out commission.ceo approve');

    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('approve', $commission->id, 'ceo')
        ->assertForbidden();
});

it('shows the Commission tab on sale agreements only, for users who can view it', function () {
    $html = fn () => Livewire::test(View::class, ['id' => $this->rentOutId, 'agreementType' => 'lease'])->html();

    expect($html())->toContain("'CommissionTab'");

    DB::table('rent_outs')->where('id', $this->rentOutId)->update(['agreement_type' => 'rental']);
    expect(Livewire::test(View::class, ['id' => $this->rentOutId, 'agreementType' => 'rental'])->html())
        ->not->toContain("'CommissionTab'");

    DB::table('rent_outs')->where('id', $this->rentOutId)->update(['agreement_type' => 'lease']);
    $this->user->revokePermissionTo('rent out commission.view');
    expect($html())->not->toContain("'CommissionTab'");
});

/** Adds a commission and moves it along the chain to the given status. */
function commissionAt(int $rentOutId, string $status, float $percentage = 5): RentOutCommission
{
    addCommission($rentOutId, ['commission_percentage' => $percentage]);
    $commission = RentOutCommission::latest('id')->first();
    $tab = Livewire::test(CommissionTab::class, ['rentOutId' => $rentOutId]);
    $steps = ['submitted' => ['submit'], 'ceo_approved' => ['submit', 'ceo'], 'finance_approved' => ['submit', 'ceo', 'finance'], 'paid' => ['submit', 'ceo', 'finance']];
    foreach ($steps[$status] ?? [] as $step) {
        $step === 'submit' ? $tab->call('submit', $commission->id) : $tab->call('approve', $commission->id, $step);
    }
    if ($status === 'paid') {
        Livewire::test(CommissionPayModal::class)->call('openModal', $commission->id)->set('form.payment_account_id', test()->cashId)->call('save');
    }

    return $commission->refresh();
}

it('lists commissions waiting on the signed-in approver and bulk-approves them at their step', function () {
    $submitted = commissionAt($this->rentOutId, 'submitted');
    $ceoApproved = commissionAt($this->rentOutId, 'ceo_approved', 4);
    $draft = commissionAt($this->rentOutId, 'draft', 3);

    $this->user->revokePermissionTo('rent out commission.owner approve');

    Livewire::test(CommissionTable::class)
        ->assertSet('filterStatus', 'waiting')
        ->assertSee('3,000.00')
        ->assertSee('2,400.00')
        ->assertDontSee('1,800.00')
        ->set('selected', [(string) $submitted->id, (string) $ceoApproved->id])
        ->call('approveSelected')
        ->assertDispatched('success', message: '2 commission(s) approved.');

    expect($submitted->refresh()->status)->toBe(CommissionStatus::CeoApproved)
        ->and($ceoApproved->refresh()->status)->toBe(CommissionStatus::FinanceApproved)
        ->and($ceoApproved->journal_id)->not->toBeNull()
        ->and($draft->refresh()->status)->toBe(CommissionStatus::Draft);
});

it('filters the commissions list by status', function () {
    commissionAt($this->rentOutId, 'paid');
    commissionAt($this->rentOutId, 'submitted', 4);

    Livewire::test(CommissionTable::class)
        ->set('filterStatus', 'paid')
        ->assertSee('3,000.00')
        ->assertDontSee('2,400.00');
});

it('reports owed commissions per salesman with paid and unpaid, leaving out drafts and rejections', function () {
    commissionAt($this->rentOutId, 'paid');
    commissionAt($this->rentOutId, 'finance_approved', 4);
    commissionAt($this->rentOutId, 'draft', 3);

    Livewire::test(CommissionReport::class)
        ->set('dateFrom', '')
        ->set('dateTo', '')
        ->assertSee('Sameer Agent')
        ->assertSee('3,000.00')
        ->assertSee('2,400.00')
        ->assertDontSee('1,800.00')
        ->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 5400.0 && $totals['paid'] === 3000.0 && $totals['unpaid'] === 2400.0)
        ->set('paid', 'unpaid')
        ->assertDontSee('3,000.00');
});

it('exports the commission report rows', function () {
    commissionAt($this->rentOutId, 'paid');

    $export = new CommissionExport(['reportable' => true]);
    $row = $export->map($export->collection()->sole());

    expect($row[0])->toBe('Sameer Agent')
        ->and($row[4])->toBe(5.0)
        ->and($row[5])->toBe(3000.0)
        ->and($row[7])->toBe('Paid');
});

it('filters the list and the report by salesman', function () {
    commissionAt($this->rentOutId, 'finance_approved');
    $other = User::factory()->create(['tenant_id' => $this->tenantId, 'type' => 'employee']);

    Livewire::test(CommissionReport::class)
        ->set('agentId', (string) $this->agent->id)->assertSee('3,000.00')
        ->set('agentId', (string) $other->id)->assertDontSee('3,000.00');

    Livewire::test(CommissionTable::class)
        ->set('filterStatus', 'all')
        ->set('agentId', (string) $this->agent->id)->assertSee('3,000.00')
        ->set('agentId', (string) $other->id)->assertDontSee('3,000.00');
});

it('selects every commission waiting on me with the header checkbox, and clears them again', function () {
    $first = commissionAt($this->rentOutId, 'submitted');
    $second = commissionAt($this->rentOutId, 'submitted', 4);
    commissionAt($this->rentOutId, 'draft', 3);

    Livewire::test(CommissionTable::class)
        ->set('filterStatus', 'all')
        ->call('toggleSelectAll')
        ->assertSet('selected', [(string) $second->id, (string) $first->id])
        ->call('toggleSelectAll')
        ->assertSet('selected', []);
});

it('shows the selected commission with its progress, figures, documents and activity', function () {
    $commission = commissionAt($this->rentOutId, 'finance_approved');

    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->assertViewHas('selected', fn ($selected) => $selected->id === $commission->id)
        ->assertViewHas('steps', fn (array $steps): bool => array_column($steps, 'state') === ['done', 'done', 'done', 'skipped', 'done', 'current'])
        ->assertViewHas('activity', fn ($activity): bool => $activity->pluck('title')->all() === ['Finance approved', 'CEO approved', 'Submitted', 'Commission entered'])
        ->assertSee('60,000.00 × 5.00 % =', false)
        ->assertSee($commission->invoice_no)
        ->assertSee('Pay Commission')
        ->assertDontSee('Add Commission');
});

it('offers Add Commission again once the only commission is rejected, and switches between commissions', function () {
    $rejected = commissionAt($this->rentOutId, 'submitted');
    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])->call('reject', $rejected->id, 'Wrong rate');

    $tab = Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->assertSee('Add Commission')
        ->assertSee('Wrong rate');

    $replacement = commissionAt($this->rentOutId, 'draft', 4);

    $tab->call('refresh')
        ->assertViewHas('selected', fn ($selected) => $selected->id === $replacement->id)
        ->assertDontSee('Add Commission')
        ->assertSee('All Commissions')
        ->call('selectCommission', $rejected->id)
        ->assertViewHas('selected', fn ($selected) => $selected->id === $rejected->id);
});

it('prints the commission invoice after finance approval and the payment voucher once paid', function () {
    $render = function (RentOutCommission $commission, string $kind): string {
        $commission->load(['agent', 'paymentAccount', 'ceoApprover', 'ownerApprover', 'financeApprover', 'payer', 'rentOut.customer', 'rentOut.property', 'rentOut.building']);

        return view('print.rentout.commission', [
            'commission' => $commission,
            'rentOut' => $commission->rentOut,
            'kind' => $kind,
            'companyName' => 'Acme', 'companyAddress' => '', 'companyPhone' => '', 'companyEmail' => '', 'companyLogo' => null,
        ])->render();
    };

    $approved = commissionAt($this->rentOutId, 'finance_approved');
    expect($render($approved, 'invoice'))
        ->toContain('Commission Invoice')
        ->toContain($approved->invoice_no)
        ->toContain('Sameer Agent')
        ->toContain('1203')
        ->toContain('60,000.00 &times; 5.00 % = <b>3,000.00</b>')
        ->not->toContain('Payment Mode');

    $this->get(route('print::rentout::commission', ['kind' => 'voucher', 'id' => $approved->id]))->assertNotFound();
    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->assertSee(route('print::rentout::commission', ['kind' => 'invoice', 'id' => $approved->id], false))
        ->assertDontSee(route('print::rentout::commission', ['kind' => 'voucher', 'id' => $approved->id], false));

    $paid = commissionAt($this->rentOutId, 'paid', 2);
    expect($render($paid, 'voucher'))
        ->toContain('Commission Payment Voucher')
        ->toContain($paid->voucherNo())
        ->toContain($paid->invoice_no)
        ->toContain('Amount Paid')
        ->toContain('Payment Mode');
    Livewire::test(CommissionTab::class, ['rentOutId' => $this->rentOutId])
        ->call('selectCommission', $paid->id)
        ->assertSee(route('print::rentout::commission', ['kind' => 'voucher', 'id' => $paid->id], false));

    $draft = commissionAt($this->rentOutId, 'draft');
    $this->get(route('print::rentout::commission', ['kind' => 'invoice', 'id' => $draft->id]))->assertNotFound();

    $this->user->revokePermissionTo('rent out commission.view');
    $this->get(route('print::rentout::commission', ['kind' => 'invoice', 'id' => $approved->id]))->assertForbidden();
});
