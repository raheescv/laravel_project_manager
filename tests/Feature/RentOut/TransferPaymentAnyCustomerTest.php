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
 * A transfer keeps the original receipt and posts a Transfer Out (debit) on the
 * source agreement plus a Transfer In (credit) on the target — any customer's —
 * sharing one balanced income-to-income journal.
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
    $this->income = Account::forceCreate(['tenant_id' => $tenant->id, 'account_type' => 'income', 'name' => 'TA Rent Income', 'slug' => 'rent_income', 'is_locked' => 1]);

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

    $this->actingAs($this->user);
});

it('keeps the receipt and posts a debit on the source and a credit on a different customer\'s property', function (): void {
    $response = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, $this->toTerm->id);

    expect($response['success'])->toBeTrue($response['message']);

    $payment = $this->payment->fresh();
    expect($payment->rent_out_id)->toBe($this->from->id)
        ->and((float) $payment->credit)->toBe(2500.0);

    $out = $response['data']['from'];
    $in = $response['data']['to'];
    expect($out->rent_out_id)->toBe($this->from->id)
        ->and((float) $out->debit)->toBe(2500.0)
        ->and((float) $out->credit)->toBe(0.0)
        ->and($out->source_id)->toBe($payment->id)
        ->and($in->rent_out_id)->toBe($this->to->id)
        ->and((float) $in->credit)->toBe(2500.0)
        ->and((float) $in->debit)->toBe(0.0)
        ->and($in->journal_id)->toBe($out->journal_id);

    $entries = JournalEntry::where('journal_id', $out->journal_id)->get();
    expect($entries)->toHaveCount(2)
        ->and((float) $entries->sum('debit'))->toBe((float) $entries->sum('credit'))
        ->and($entries->pluck('account_id')->unique()->all())->toBe([$this->income->id]);

    expect($this->fromTerm->fresh()->status)->toBe('pending')
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(0.0)
        ->and($this->toTerm->fresh()->status)->toBe('paid');
});

it('transfers part of a receipt and releases only that much of the source term', function (): void {
    $response = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, $this->toTerm->id, '', 1000);

    expect($response['success'])->toBeTrue($response['message'])
        ->and((float) $response['data']['from']->debit)->toBe(1000.0)
        ->and((float) $response['data']['to']->credit)->toBe(1000.0)
        ->and((float) JournalEntry::where('journal_id', $response['data']['journal_id'])->sum('debit'))->toBe(1000.0)
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(1500.0)
        ->and((float) $this->toTerm->fresh()->paid)->toBe(1000.0)
        ->and(TransferTransactionAction::transferableAmount($this->payment))->toBe(1500.0);
});

it('splits a receipt across transfers until nothing is left', function (): void {
    $first = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, null, '', 1000);
    $tooMuch = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, null, '', 1600);
    $rest = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id);
    $again = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, null, '', 1);

    expect($first['success'])->toBeTrue()
        ->and($tooMuch['success'])->toBeFalse()
        ->and($tooMuch['message'])->toBe('Transfer amount cannot exceed the 1,500.00 left on this payment.')
        ->and($rest['success'])->toBeTrue()
        ->and((float) $rest['data']['from']->debit)->toBe(1500.0)
        ->and($again['success'])->toBeFalse()
        ->and($again['message'])->toBe('This payment has already been fully transferred.')
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(0.0);
});

it('rejects a zero transfer amount', function (): void {
    $response = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, null, '', 0);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toBe('Transfer amount must be greater than zero.');
});

it('validates the modal amount against what is left on the receipt', function (): void {
    $this->user->givePermissionTo(Permission::findOrCreate('rent out.transfer payment', 'web'));

    Livewire::test(TransferPaymentModal::class)
        ->call('openModal', $this->payment->id)
        ->assertSet('available', 2500.0)
        ->assertSet('form.amount', 2500.0)
        ->call('selectTarget', $this->to->id, '#'.$this->to->id)
        ->set('form.amount', 3000)
        ->call('transfer')
        ->assertHasErrors(['form.amount' => 'max'])
        ->set('form.amount', 400)
        ->call('transfer')
        ->assertHasNoErrors();

    expect((float) RentOutTransaction::where('source', 'Transfer')->where('rent_out_id', $this->to->id)->value('credit'))->toBe(400.0);
});

it('unwinds both rows, the journal and both terms when the transfer is reversed', function (): void {
    $response = (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, $this->toTerm->id);
    $journalId = $response['data']['journal_id'];

    (new ReverseTransactionAction())->reverse($response['data']['to']);

    expect(RentOutTransaction::where('source', 'Transfer')->count())->toBe(0)
        ->and(Journal::find($journalId))->toBeNull()
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(2500.0)
        ->and((float) $this->toTerm->fresh()->paid)->toBe(0.0)
        ->and($this->payment->fresh())->not->toBeNull();
});

it('unwinds the transfer first when the original receipt is deleted', function (): void {
    (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, $this->toTerm->id);

    (new ReverseTransactionAction())->reverse($this->payment->fresh());

    expect(RentOutTransaction::where('source', 'Transfer')->count())->toBe(0)
        ->and((float) $this->toTerm->fresh()->paid)->toBe(0.0)
        ->and((float) $this->fromTerm->fresh()->paid)->toBe(0.0);
});

it('lists the transfer out on the source payments tab and nets it from the total', function (): void {
    (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, null, '', 2000);

    $component = Livewire::test(PaymentTab::class, ['rentOutId' => $this->from->id]);

    expect($component->viewData('payments')->pluck('debit')->map(fn ($v) => (float) $v)->all())->toContain(2000.0);

    $component->assertSeeHtml('&minus;2,000.00')
        ->assertSeeHtml('&minus; 2,000.00 transferred out')
        ->assertSeeHtml('<td class="py-2 text-end text-nowrap" x-show="columns.credit" data-label="Total">');

    $footer = str($component->html())->after('<tfoot')->before('</tfoot>');
    expect(substr_count($footer, '<td') + 1)->toBe(15) // colspan=2 cell counts twice
        ->and((string) $footer)->toContain('500.00');

    $summary = $component->viewData('sourceSummary')->keyBy('source');
    expect((float) $summary['Transfer']->total_credit)->toBe(-2000.0)
        ->and((float) $summary['PaymentTerm']->total_credit)->toBe(2500.0);
});

it('shows receipts and both sides of a transfer on the rent statements', function (): void {
    (new TransferTransactionAction())->execute($this->payment->id, $this->to->id, null, '', 2000);
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
