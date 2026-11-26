<?php

use App\Actions\RentOut\Payment\ReverseTransactionAction;
use App\Actions\RentOut\Payment\TransferTransactionAction;
use App\Actions\RentOut\StatementEntriesAction;
use App\Livewire\RentOut\Tabs\PaymentTab;
use App\Livewire\RentOut\Tabs\TransferPaymentModal;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * A transfer is standalone (like a payout): any amount, not tied to a receipt.
 * It posts a Transfer Out (debit) on the source agreement plus a Transfer In
 * (credit) on the target — any customer's — sharing one balanced
 * customer-to-customer journal.
 */
beforeEach(function (): void {
    $tenant = Tenant::create(['name' => 'TA Tenant', 'subdomain' => 'ta'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'T']);

    $this->user = User::create([
        'tenant_id' => $tenant->id, 'name' => 'TA User', 'type' => 'employee', 'is_admin' => 1,
        'email' => 'ta'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'TA Branch', 'code' => 'TA']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'TA Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'TA Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'TA Type']);
    $this->cash = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'TA Cash']);

    $makeRentOut = function (string $customerName, string $unit) use ($tenant, $branch, $group, $building, $type): RentOut {
        $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => $customerName]);
        $property = Property::create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
            'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => $unit,
        ]);

        return RentOut::create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
            'salesman_id' => $this->user->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
            'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => 'rental',
            'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(), 'created_by' => $this->user->id,
        ]);
    };

    $this->from = $makeRentOut('TA Customer One', 'TA-101');
    $this->to = $makeRentOut('TA Customer Two', 'TA-102');

    $this->fromTerm = RentOutPaymentTerm::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'rent_out_id' => $this->from->id,
        'amount' => 2500, 'paid' => 2500, 'due_date' => now()->toDateString(), 'status' => 'paid',
    ]);
    $this->toTerm = RentOutPaymentTerm::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'rent_out_id' => $this->to->id,
        'amount' => 2500, 'paid' => 0, 'due_date' => now()->toDateString(), 'status' => 'pending',
    ]);

    $this->payment = RentOutTransaction::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'rent_out_id' => $this->from->id,
        'date' => now()->toDateString(), 'credit' => 2500, 'debit' => 0, 'account_id' => $this->cash->id,
        'source' => 'PaymentTerm', 'source_id' => $this->fromTerm->id,
        'model' => 'RentOutPaymentTerm', 'model_id' => $this->fromTerm->id,
        'group' => 'Rent Payment', 'created_by' => $this->user->id,
    ]);

    $receiptJournal = Journal::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'date' => now()->toDateString(),
        'description' => 'Rent Payment', 'source' => 'rent_out', 'model' => 'RentOut', 'model_id' => $this->from->id,
        'created_by' => $this->user->id,
    ]);
    foreach ([[$this->cash->id, $this->from->account_id, 2500, 0], [$this->from->account_id, $this->cash->id, 0, 2500]] as [$account, $counter, $debit, $credit]) {
        JournalEntry::create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'journal_id' => $receiptJournal->id, 'date' => now()->toDateString(),
            'account_id' => $account, 'counter_account_id' => $counter, 'debit' => $debit, 'credit' => $credit,
            'source' => 'rent_out', 'model' => 'RentOut', 'model_id' => $this->from->id, 'created_by' => $this->user->id,
        ]);
    }
    $this->payment->update(['journal_id' => $receiptJournal->id]);

    $this->actingAs($this->user);
});

it('posts a transfer out on the source and a transfer in on a different customer\'s property', function (): void {
    $response = (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 2500, $this->toTerm->id, 'Move', '2026-11-20');

    expect($response['success'])->toBeTrue($response['message']);

    $out = $response['data']['from'];
    $in = $response['data']['to'];
    expect($out->rent_out_id)->toBe($this->from->id)
        ->and((float) $out->debit)->toBe(2500.0)
        ->and((float) $out->credit)->toBe(0.0)
        ->and($out->source_id)->toBeNull()
        ->and($out->date->format('Y-m-d'))->toBe('2026-11-20')
        ->and($in->rent_out_id)->toBe($this->to->id)
        ->and((float) $in->credit)->toBe(2500.0)
        ->and((float) $in->debit)->toBe(0.0)
        ->and($in->journal_id)->toBe($out->journal_id);

    $entries = JournalEntry::where('journal_id', $out->journal_id)->get();
    $debitLeg = $entries->firstWhere('debit', '>', 0);
    $creditLeg = $entries->firstWhere('credit', '>', 0);
    expect($entries)->toHaveCount(2)
        ->and((float) $entries->sum('debit'))->toBe((float) $entries->sum('credit'))
        ->and($debitLeg->account_id)->toBe($this->from->account_id)
        ->and($creditLeg->account_id)->toBe($this->to->account_id)
        ->and($debitLeg->counter_account_id)->toBe($this->to->account_id)
        ->and($creditLeg->counter_account_id)->toBe($this->from->account_id);

    // Standalone: the existing receipt and its term are left alone.
    expect((float) $this->payment->fresh()->credit)->toBe(2500.0)
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(2500.0)
        ->and($this->toTerm->fresh()->status)->toBe('paid');
});

it('transfers any amount, even more than has been received', function (): void {
    $response = (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 4000);

    expect($response['success'])->toBeTrue($response['message'])
        ->and((float) $response['data']['from']->debit)->toBe(4000.0)
        ->and((float) JournalEntry::where('journal_id', $response['data']['journal_id'])->sum('debit'))->toBe(4000.0)
        ->and((float) $this->toTerm->fresh()->paid)->toBe(0.0);
});

it('rejects a zero amount or the same property', function (): void {
    $zero = (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 0);
    $same = (new TransferTransactionAction())->execute($this->from->id, $this->from->id, 100);

    expect($zero['success'])->toBeFalse()
        ->and($zero['message'])->toBe('Transfer amount must be greater than zero.')
        ->and($same['success'])->toBeFalse()
        ->and($same['message'])->toBe('Cannot transfer to the same property.');
});

it('opens from the payments tab and validates the modal', function (): void {
    $this->user->givePermissionTo(Permission::findOrCreate('rent out.transfer payment', 'web'));

    Livewire::test(PaymentTab::class, ['rentOutId' => $this->from->id])
        ->assertSeeHtml('wire:click="openTransferModal"')
        ->call('openTransferModal')
        ->assertDispatched('open-transfer-modal', rentOutId: $this->from->id);

    Livewire::test(TransferPaymentModal::class)
        ->call('openModal', $this->from->id)
        ->assertSet('form.date', now()->format('Y-m-d'))
        ->call('transfer')
        ->assertHasErrors(['form.to_rent_out_id' => 'required', 'form.amount' => 'required'])
        ->call('selectTarget', $this->to->id, '#'.$this->to->id)
        ->set('form.amount', 400)
        ->call('transfer')
        ->assertHasNoErrors();

    expect((float) RentOutTransaction::where('source', 'Transfer')->where('rent_out_id', $this->to->id)->value('credit'))->toBe(400.0)
        ->and((float) RentOutTransaction::where('source', 'Transfer')->where('rent_out_id', $this->from->id)->value('debit'))->toBe(400.0);
});

it('hides the transfer button without the permission', function (): void {
    $this->user->update(['is_admin' => 0]);

    Livewire::test(PaymentTab::class, ['rentOutId' => $this->from->id])
        ->assertDontSeeHtml('wire:click="openTransferModal"');
});

it('unwinds both rows, the journal and the target term when the transfer is reversed', function (): void {
    $response = (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 2500, $this->toTerm->id);
    $journalId = $response['data']['journal_id'];

    (new ReverseTransactionAction())->reverse($response['data']['from']);

    expect(RentOutTransaction::where('source', 'Transfer')->count())->toBe(0)
        ->and(Journal::find($journalId))->toBeNull()
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(2500.0)
        ->and((float) $this->toTerm->fresh()->paid)->toBe(0.0)
        ->and($this->payment->fresh())->not->toBeNull();
});

it('lists the transfer out on the source payments tab and nets it from the total', function (): void {
    (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 2000);

    $component = Livewire::test(PaymentTab::class, ['rentOutId' => $this->from->id]);

    expect($component->viewData('payments')->pluck('debit')->map(fn ($v) => (float) $v)->all())->toContain(2000.0);

    $component->assertSeeHtml('&minus;2,000.00')
        ->assertSeeHtml('&minus; 2,000.00 transferred out')
        ->assertSeeHtml('<td class="py-2 text-end text-nowrap" x-show="columns.credit" data-label="Total">');

    $footer = str($component->html())->after('<tfoot')->before('</tfoot>');
    expect(substr_count($footer, '<td') + 1)->toBe(15) // colspan=2 cell counts twice
        ->and((string) $footer)->toContain('500.00')
        // An !important display utility would override x-show and render hidden columns.
        ->and((string) $footer)->not->toContain('-table-cell');

    $summary = $component->viewData('sourceSummary')->keyBy('source');
    expect((float) $summary['Transfer']->total_credit)->toBe(-2000.0)
        ->and((float) $summary['PaymentTerm']->total_credit)->toBe(2500.0);
});

it('shows receipts and both sides of a transfer on the rent statements', function (): void {
    (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 2000);
    RentOutTransaction::create([
        'tenant_id' => $this->from->tenant_id, 'branch_id' => $this->from->branch_id, 'rent_out_id' => $this->from->id,
        'date' => now()->toDateString(), 'credit' => 300, 'debit' => 0, 'account_id' => $this->cash->id,
        'source' => 'UtilityTerm', 'group' => 'Utility Payment', 'created_by' => $this->user->id,
    ]);

    $from = (new StatementEntriesAction())->execute($this->from->load('paymentTerms'));
    $to = (new StatementEntriesAction())->execute($this->to->load('paymentTerms'));

    expect($from->pluck('payment_mode')->all())->toBe(['Rent Due', 'TA Cash', 'Transfer Out'])
        ->and($from->sum('debit'))->toBe(4500.0)
        ->and($from->sum('credit'))->toBe(2500.0)
        ->and($to->pluck('payment_mode')->all())->toBe(['Rent Due', 'Transfer In'])
        ->and($to->sum('credit'))->toBe(2000.0);
});

it('lists payouts on the payments tab and nets them from the total', function (): void {
    (new TransferTransactionAction())->execute($this->from->id, $this->to->id, 1000);
    RentOutTransaction::create([
        'tenant_id' => $this->from->tenant_id, 'branch_id' => $this->from->branch_id, 'rent_out_id' => $this->from->id,
        'date' => now()->toDateString(), 'credit' => 0, 'debit' => 300, 'account_id' => $this->cash->id,
        'source' => 'Payout', 'group' => 'Payout', 'payment_type' => 'Payout', 'created_by' => $this->user->id,
    ]);

    $component = Livewire::test(PaymentTab::class, ['rentOutId' => $this->from->id]);

    expect($component->viewData('payments')->pluck('source')->all())->toContain('Payout')
        ->and($component->viewData('sourceSummary')->keyBy('source')['Payout']->total_credit)->toEqual(-300);

    $component->assertSeeHtml('&minus;300.00')
        ->assertSeeHtml('&minus; 1,000.00 transferred out')
        ->assertSeeHtml('&minus; 300.00 paid out')
        ->assertSee('1,200.00');
});
