<?php

use App\Enums\ProjectContract\ContractStatus;
use App\Models\Configuration;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentTerm;
use App\Models\User;
use App\Support\ProjectContract\SystemAccounts;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PosWorld;

/**
 * Agreement Module: contracts with vendors, their approval levels, terms,
 * payments and the journals those payments post.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module', 'value' => 'Agreement Module']);

    $this->grant = function (User $user): User {
        foreach (config('permissions.project contract') as $action) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => "project contract.{$action}", 'guard_name' => 'web']));
        }
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => "vendor.{$action}", 'guard_name' => 'web']));
        }

        return $user;
    };

    $this->person = fn (): User => ($this->grant)(User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'is_admin' => 1,
    ]));

    ($this->grant)($this->world->user);
    $this->approverOne = ($this->person)();
    $this->approverTwo = ($this->person)();

    $this->as = fn (User $user) => $this->actingAs($user)->withSession(['branch_id' => $this->world->branch->id]);
    ($this->as)($this->world->user);

    $this->api = fn (string $path = ''): string => $this->world->url('/project-contract/api'.$path);

    $this->vendorId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Gulf Steel Contracting',
        'account_type' => 'liability',
        'model' => 'Vendor',
        'mobile' => '55512345',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->payload = fn (array $overrides = []): array => $overrides + [
        'project_name' => 'Al Wakra Villa Fit-out',
        'account_id' => $this->vendorId,
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
        'no_of_terms' => 5,
        'payment_frequency' => 'milestone',
        'contract_amount' => 100000,
    ];

    /** Create, submit and approve through both levels; returns the active contract. */
    $this->activeContract = function (array $overrides = []): ProjectContract {
        ($this->as)($this->world->user);
        $id = $this->postJson(($this->api)('/contracts'), ($this->payload)($overrides + ['submit' => true]))->assertCreated()->json('data.id');
        foreach ([$this->approverOne, $this->approverTwo] as $approver) {
            ($this->as)($approver);
            $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'contract', 'subject_id' => $id, 'decision' => 'approve'])->assertSuccessful();
        }
        ($this->as)($this->world->user);

        return ProjectContract::findOrFail($id);
    };

    $this->term = fn (ProjectContract $contract, array $overrides = []): int => $this->postJson(($this->api)("/contracts/{$contract->id}/terms"), $overrides + [
        'label' => 'Mobilisation 20%', 'due_date' => '2026-09-05', 'amount' => 20000,
    ])->assertSuccessful()->json('data.terms.0.id');
});

it('serves the module screens only when the Agreement Module is the active system', function (): void {
    $this->withoutVite();

    $this->get($this->world->url('/project-contract'))->assertOk()->assertSee('id="project-contract-app"', false);
    $this->get($this->world->url('/project-contract/vendors'))->assertOk();

    Configuration::where('key', 'active_module')->update(['value' => 'POS Module']);
    $this->get($this->world->url('/project-contract'))->assertNotFound();
    $this->getJson(($this->api)('/contracts'))->assertNotFound();
});

it('guards every endpoint by permission', function (): void {
    $this->world->user->revokePermissionTo('project contract.create');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->postJson(($this->api)('/contracts'), ($this->payload)())->assertForbidden();
});

it('saves a draft with a PC reference and rejects bad input', function (): void {
    $id = $this->postJson(($this->api)('/contracts'), ($this->payload)())->assertCreated()->json('data.id');

    $contract = ProjectContract::findOrFail($id);
    expect($contract->status)->toBe(ContractStatus::Draft)
        ->and($contract->reference_no)->toStartWith('PC-')
        ->and($contract->branch_id)->toBe($this->world->branch->id);

    $this->postJson(($this->api)('/contracts'), ($this->payload)(['end_date' => '2026-08-01']))->assertUnprocessable()->assertJsonValidationErrors('end_date');
    $this->postJson(($this->api)('/contracts'), ($this->payload)(['account_id' => $this->world->cashAccountId]))
        ->assertUnprocessable()->assertJsonPath('message', 'Choose a vendor account for this contract.');
});

it('runs a contract through both approval levels in order, by permission alone', function (): void {
    $id = $this->postJson(($this->api)('/contracts'), ($this->payload)(['submit' => true]))->assertCreated()->json('data.id');
    expect(ProjectContract::find($id)->status)->toBe(ContractStatus::PendingApproval)
        ->and(ProjectContractApproval::where('approvable_id', $id)->count())->toBe(2);

    $decide = fn (string $decision = 'approve', ?string $comment = null) => $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'contract', 'subject_id' => $id, 'decision' => $decision, 'comment' => $comment]);

    // The submitter holds the approve permission, so they may approve every level themselves.
    $decide()->assertSuccessful()->assertJsonPath('message', 'Level 1 approved. Waiting for the next level.');
    expect(ProjectContract::find($id)->status)->toBe(ContractStatus::PendingApproval);

    $decide()->assertSuccessful()->assertJsonPath('data.status', 'active');
    expect(ProjectContract::find($id)->status)->toBe(ContractStatus::Active)
        ->and(ProjectContractApproval::where('approvable_id', $id)->where('status', 'approved')->pluck('acted_by')->unique()->all())->toBe([$this->world->user->id]);
});

it('refuses a decision from someone without the approve permission', function (): void {
    $id = $this->postJson(($this->api)('/contracts'), ($this->payload)(['submit' => true]))->json('data.id');

    $this->world->user->revokePermissionTo('project contract.approve');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'contract', 'subject_id' => $id, 'decision' => 'approve'])->assertForbidden();
});

it('needs only one level below the Admin threshold', function (): void {
    $id = $this->postJson(($this->api)('/contracts'), ($this->payload)(['contract_amount' => 30000, 'submit' => true]))->json('data.id');

    expect(ProjectContractApproval::where('approvable_id', $id)->pluck('approver_role')->all())->toBe(['Project Manager']);
});

it('sends a rejected contract back for edits and opens a fresh round on resubmit', function (): void {
    $id = $this->postJson(($this->api)('/contracts'), ($this->payload)(['submit' => true]))->json('data.id');

    ($this->as)($this->approverOne);
    $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'contract', 'subject_id' => $id, 'decision' => 'reject'])->assertUnprocessable();
    $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'contract', 'subject_id' => $id, 'decision' => 'reject', 'comment' => 'Wrong amount'])->assertSuccessful();
    expect(ProjectContract::find($id)->status)->toBe(ContractStatus::Rejected);

    ($this->as)($this->world->user);
    $this->putJson(($this->api)("/contracts/{$id}"), ($this->payload)(['contract_amount' => 90000, 'submit' => true]))->assertSuccessful();

    expect(ProjectContract::find($id)->status)->toBe(ContractStatus::PendingApproval)
        ->and(ProjectContractApproval::where('approvable_id', $id)->where('round', 2)->where('status', 'pending')->count())->toBe(2);

    ($this->as)($this->approverOne);
    $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'contract', 'subject_id' => $id, 'decision' => 'approve'])
        ->assertSuccessful()->assertJsonPath('message', 'Level 1 approved. Waiting for the next level.');
});

it('locks the money fields of an approved contract', function (): void {
    $contract = ($this->activeContract)();

    $this->putJson(($this->api)("/contracts/{$contract->id}"), ($this->payload)(['contract_amount' => 150000]))
        ->assertUnprocessable()->assertJsonPath('message', 'The vendor, dates, amount and terms of an approved contract change only through an extension.');

    $this->putJson(($this->api)("/contracts/{$contract->id}"), ($this->payload)(['project_name' => 'Renamed project']))->assertSuccessful();
    expect($contract->fresh()->project_name)->toBe('Renamed project');
});

it('adds terms one at a time and only warns when they pass the contract amount', function (): void {
    $contract = ($this->activeContract)(['no_of_terms' => 1]);

    $this->postJson(($this->api)("/contracts/{$contract->id}/terms"), ['label' => 'Advance', 'due_date' => '2026-09-05', 'amount' => 80000])
        ->assertSuccessful()->assertJsonMissingPath('data.warnings');

    $this->postJson(($this->api)("/contracts/{$contract->id}/terms"), ['label' => 'Extra works', 'due_date' => '2026-12-01', 'amount' => 30000])
        ->assertSuccessful()
        ->assertJsonPath('data.warnings.0', 'Payment terms now total 10,000.00 more than the contract amount. Extend the contract if the amount has grown.')
        ->assertJsonPath('data.warnings.1', 'This contract now has 2 terms; 1 were planned.');

    expect($contract->terms()->count())->toBe(2);
});

it('refuses terms on a contract that is not yet approved', function (): void {
    $id = $this->postJson(($this->api)('/contracts'), ($this->payload)())->json('data.id');

    $this->postJson(($this->api)("/contracts/{$id}/terms"), ['label' => 'Advance', 'due_date' => '2026-09-05', 'amount' => 1000])
        ->assertUnprocessable()->assertJsonPath('message', 'Payment terms can be added once the contract is approved and active.');
});

it('pays across terms oldest first and posts Dr Vendor / Cr payment method', function (): void {
    $contract = ($this->activeContract)();
    $first = ($this->term)($contract, ['label' => 'Mobilisation', 'due_date' => '2026-09-05', 'amount' => 20000]);
    ($this->term)($contract, ['label' => 'Structure', 'due_date' => '2026-11-15', 'amount' => 30000]);
    $termIds = $contract->terms()->pluck('id')->all();

    $this->postJson(($this->api)("/contracts/{$contract->id}/payments"), [
        'term_ids' => $termIds, 'date' => today()->toDateString(), 'amount' => 60000, 'payment_method_id' => $this->world->cashAccountId,
    ])->assertUnprocessable()->assertJsonPath('message', 'The amount is more than the 50,000.00 still due on the chosen terms.');

    $this->postJson(($this->api)("/contracts/{$contract->id}/payments"), [
        'term_ids' => $termIds, 'date' => today()->toDateString(), 'amount' => 35000, 'payment_method_id' => $this->world->cashAccountId,
    ])->assertSuccessful()->assertJsonPath('data.totals.paid', 35000);

    $terms = ProjectContractPaymentTerm::where('project_contract_id', $contract->id)->orderBy('due_date')->get();
    expect($terms[0]->status->value)->toBe('paid')
        ->and((float) $terms[1]->paid)->toBe(15000.0)
        ->and($terms[1]->status->value)->toBe('partial')
        ->and($terms[0]->id)->toBe($first);

    $payment = ProjectContractPayment::where('project_contract_id', $contract->id)->firstOrFail();
    $entries = JournalEntry::where('journal_id', $payment->journal_id)->get();
    expect($entries)->toHaveCount(2)
        ->and((float) $entries->sum('debit'))->toBe((float) $entries->sum('credit'))
        ->and((float) $entries->firstWhere('account_id', $this->vendorId)->debit)->toBe(35000.0)
        ->and((float) $entries->firstWhere('account_id', $this->world->cashAccountId)->credit)->toBe(35000.0)
        ->and($entries->firstWhere('account_id', $this->vendorId)->counter_account_id)->toBe($this->world->cashAccountId)
        ->and(Journal::find($payment->journal_id)->source)->toBe('project_contract');
});

it('reverses a payment with a contra journal and gives the amount back to its terms', function (): void {
    $contract = ($this->activeContract)();
    ($this->term)($contract);
    $termId = $contract->terms()->value('id');

    $this->postJson(($this->api)("/contracts/{$contract->id}/payments"), [
        'term_ids' => [$termId], 'date' => today()->toDateString(), 'amount' => 20000, 'payment_method_id' => $this->world->cashAccountId,
    ])->assertSuccessful();
    $payment = ProjectContractPayment::firstOrFail();

    $this->postJson(($this->api)("/contracts/{$contract->id}/payments/{$payment->id}/reverse"), [])->assertUnprocessable();
    $this->postJson(($this->api)("/contracts/{$contract->id}/payments/{$payment->id}/reverse"), ['reason' => 'Paid to the wrong account'])
        ->assertSuccessful()->assertJsonPath('data.totals.paid', 0);

    $payment->refresh();
    $contra = JournalEntry::where('journal_id', $payment->reversal_journal_id)->get();
    expect($payment->status)->toBe(ProjectContractPayment::STATUS_REVERSED)
        ->and((float) $contra->firstWhere('account_id', $this->vendorId)->credit)->toBe(20000.0)
        ->and((float) $contra->firstWhere('account_id', $this->world->cashAccountId)->debit)->toBe(20000.0)
        ->and(JournalEntry::where('journal_id', $payment->journal_id)->count())->toBe(2)
        ->and(ProjectContractPaymentTerm::find($termId)->status->value)->toBe('pending');
});

it('applies an extension only after every level approves', function (): void {
    $contract = ($this->activeContract)();

    $extensionId = $this->postJson(($this->api)("/contracts/{$contract->id}/extensions"), [
        'new_end_date' => '2027-08-31', 'additional_amount' => 20000, 'additional_terms' => 1, 'reason' => 'Boundary wall added',
    ])->assertSuccessful()->assertJsonPath('message', 'Extension sent for approval.')->json('data.extensions.0.id');

    expect($contract->fresh()->end_date->toDateString())->toBe('2027-06-30');

    foreach ([$this->approverOne, $this->approverTwo] as $approver) {
        ($this->as)($approver);
        $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'extension', 'subject_id' => $extensionId, 'decision' => 'approve'])->assertSuccessful();
    }

    $contract->refresh();
    expect($contract->end_date->toDateString())->toBe('2027-08-31')
        ->and((float) $contract->contract_amount)->toBe(120000.0)
        ->and($contract->no_of_terms)->toBe(6);
});

it('posts cash security, tracks guarantees without a journal, and settles through approval', function (): void {
    $contract = ($this->activeContract)();

    $this->postJson(($this->api)("/contracts/{$contract->id}/securities"), [
        'direction' => 'received', 'form' => 'bank_guarantee', 'purpose' => 'performance_bond', 'amount' => 12000,
        'bank_name' => 'QNB', 'reference_no' => 'BG/2026/4471', 'valid_from' => '2026-09-01', 'valid_until' => '2026-12-31',
    ])->assertSuccessful();

    $response = $this->postJson(($this->api)("/contracts/{$contract->id}/securities"), [
        'direction' => 'received', 'form' => 'cash', 'purpose' => 'general', 'amount' => 6000, 'payment_method_id' => $this->world->cashAccountId,
    ])->assertSuccessful();

    $securities = $contract->securities()->get()->keyBy(fn ($security) => $security->form->value);
    $depositAccount = SystemAccounts::id(SystemAccounts::VENDOR_SECURITY_DEPOSIT);
    expect($securities['bank_guarantee']->journal_id)->toBeNull()
        ->and((float) JournalEntry::where('journal_id', $securities['cash']->journal_id)->where('account_id', $depositAccount)->value('credit'))->toBe(6000.0)
        ->and($response->json('data.totals.security_held'))->toEqual(18000);

    $cash = $securities['cash'];
    $this->postJson(($this->api)("/contracts/{$contract->id}/securities/{$cash->id}/settle"), ['type' => 'forfeit', 'amount' => 1000, 'date' => today()->toDateString()])
        ->assertUnprocessable()->assertJsonValidationErrors('reason');
    $this->postJson(($this->api)("/contracts/{$contract->id}/securities/{$cash->id}/settle"), ['type' => 'return', 'amount' => 9000, 'date' => today()->toDateString(), 'payment_method_id' => $this->world->cashAccountId])
        ->assertUnprocessable()->assertJsonPath('message', 'Only 6,000.00 of this security is free to settle.');

    $settlementId = $this->postJson(($this->api)("/contracts/{$contract->id}/securities/{$cash->id}/settle"), [
        'type' => 'return', 'amount' => 4000, 'date' => today()->toDateString(), 'payment_method_id' => $this->world->cashAccountId,
    ])->assertSuccessful()->assertJsonPath('message', 'Sent for approval.')->json('data.securities.0.settlements.0.id');

    $this->postJson(($this->api)("/contracts/{$contract->id}/status"), ['status' => 'completed'])
        ->assertUnprocessable()->assertJsonPath('message', 'Return or forfeit every security before completing this contract.');

    ($this->as)($this->approverOne);
    $this->postJson(($this->api)('/approvals/decide'), ['subject' => 'settlement', 'subject_id' => $settlementId, 'decision' => 'approve'])->assertSuccessful();

    $cash->load('settlements');
    expect($cash->heldAmount())->toBe(2000.0)
        ->and($cash->state())->toBe('partially_settled')
        ->and((float) JournalEntry::where('model', 'ProjectContractSecuritySettlement')->where('account_id', $depositAccount)->value('debit'))->toBe(4000.0);
});

it('creates, lists and protects vendors', function (): void {
    $id = $this->postJson(($this->api)('/vendors'), [
        'name' => 'Al Noor Electromech', 'alias_name' => 'النور', 'vendor_type' => 'subcontractor', 'mobile' => '55102030',
        'email' => 'info@alnoor.qa', 'cr_number' => 'CR 99012', 'nationality' => 'Qatar',
    ])->assertCreated()->json('data.id');

    $this->assertDatabaseHas('accounts', ['id' => $id, 'model' => 'Vendor', 'account_type' => 'liability', 'alias_name' => 'النور', 'vendor_type' => 'subcontractor', 'whatsapp_mobile' => '55102030']);
    $this->postJson(($this->api)('/vendors'), ['name' => 'Al Noor Electromech', 'mobile' => '1'])->assertUnprocessable()->assertJsonValidationErrors('name');

    $this->getJson(($this->api)('/vendors?search=Noor'))->assertSuccessful()->assertJsonPath('data.rows.0.registration', 'CR 99012');

    ($this->activeContract)();
    $this->deleteJson(($this->api)("/vendors/{$this->vendorId}"))->assertUnprocessable()->assertJsonPath('message', 'Gulf Steel Contracting has 1 contract and cannot be deleted.');
    $this->deleteJson(($this->api)("/vendors/{$id}"))->assertSuccessful();
});

it('reports pending and paid payments and fills the main dashboard widgets', function (): void {
    $contract = ($this->activeContract)();
    ($this->term)($contract, ['label' => 'Overdue term', 'due_date' => today()->subDays(10)->toDateString(), 'amount' => 20000]);
    ($this->term)($contract, ['label' => 'Later term', 'due_date' => today()->addDays(60)->toDateString(), 'amount' => 30000]);
    $overdueId = $contract->terms()->where('label', 'Overdue term')->value('id');
    $this->postJson(($this->api)("/contracts/{$contract->id}/payments"), [
        'term_ids' => [$overdueId], 'date' => today()->toDateString(), 'amount' => 5000, 'payment_method_id' => $this->world->cashAccountId,
    ])->assertSuccessful();

    $this->getJson(($this->api)('/reports/pending'))->assertSuccessful()
        ->assertJsonPath('data.summary.total', 45000)
        ->assertJsonPath('data.summary.overdue', 15000)
        ->assertJsonPath('data.summary.later', 30000)
        ->assertJsonPath('data.summary.ageing.d30', 15000);
    $this->getJson(($this->api)('/reports/pending?state=overdue'))->assertJsonCount(1, 'data.rows');

    $this->getJson(($this->api)('/reports/paid'))->assertSuccessful()->assertJsonPath('data.summary.total', 5000)->assertJsonCount(1, 'data.rows');

    $widget = fn (string $key, array $query = []) => $this->getJson($this->world->url("/dashboard/widgets/{$key}").($query ? '?'.http_build_query($query) : ''))->assertOk();

    $kpis = collect($widget('contract-kpis', ['option' => 'all'])->json('blocks.0.items'))->keyBy('label');
    expect($kpis['Committed']['value'])->toEqual(100000)
        ->and($kpis['Paid']['value'])->toEqual(5000)
        ->and($kpis['Balance']['value'])->toEqual(95000);

    $attention = collect($widget('contract-attention')->json('blocks'))->firstWhere('type', 'kpis');
    expect(collect($attention['items'])->firstWhere('label', 'Overdue amount')['value'])->toEqual(15000);
    expect($widget('contract-vendors', ['option' => 'all'])->json('blocks.0.rows.0.label'))->toBe('Gulf Steel Contracting');
    expect($widget('contract-due')->json('blocks.0.rows'))->toHaveCount(2);
});

it('prints the contract statement with its terms and payments', function (): void {
    $contract = ($this->activeContract)();
    ($this->term)($contract, ['label' => 'Mobilisation 20%', 'due_date' => '2026-09-05', 'amount' => 20000]);
    $this->postJson(($this->api)("/contracts/{$contract->id}/payments"), [
        'term_ids' => [$contract->terms()->value('id')], 'date' => today()->toDateString(), 'amount' => 8000, 'payment_method_id' => $this->world->cashAccountId,
    ])->assertSuccessful();

    $html = view('print.project-contract.statement', [
        'contract' => $contract->fresh(['vendor', 'branch', 'terms', 'payments']),
        'entries' => collect([
            ['date' => '2026-09-05', 'description' => 'Mobilisation 20%', 'payment_mode' => 'Term due', 'cheque_no' => '', 'debit' => 20000.0, 'credit' => 0, 'remark' => ''],
            ['date' => today()->toDateString(), 'description' => 'PCP-0001 · Mobilisation 20%', 'payment_mode' => 'Cash', 'cheque_no' => '', 'debit' => 0, 'credit' => 8000.0, 'remark' => ''],
        ]),
        'fromDate' => null, 'toDate' => null, 'paidTotal' => 8000.0,
        'companyName' => 'Acme', 'companyAddress' => '', 'companyPhone' => '', 'companyEmail' => '', 'companyLogo' => null,
    ])->render();

    expect($html)->toContain('Contract Statement')->toContain('Gulf Steel Contracting')->toContain('Mobilisation 20%')->toContain($contract->reference_no);

    $this->get($this->world->url("/print/project-contract/statement/{$contract->id}"))->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $payment = ProjectContractPayment::firstOrFail();
    foreach (['receipt' => 'Payment Receipt', 'voucher' => 'Payment Voucher'] as $kind => $title) {
        $html = view('print.project-contract.payment', [
            'payment' => $payment->load(['paymentMethod', 'creator', 'allocations.term', 'contract.vendor']),
            'contract' => $payment->contract, 'kind' => $kind,
            'companyName' => 'Acme', 'companyAddress' => '', 'companyPhone' => '', 'companyEmail' => '', 'companyLogo' => null,
        ])->render();
        expect($html)->toContain($title)->toContain($payment->voucher_no)->toContain('Gulf Steel Contracting')->toContain('Mobilisation 20%');
        $this->get($this->world->url("/print/project-contract/payment-{$kind}/{$payment->id}"))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
    $this->get($this->world->url("/print/project-contract/voucher/{$contract->id}"))->assertOk()->assertHeader('Content-Type', 'application/pdf');

    // A term prints as an invoice for its amount, and as a receipt / voucher for what was paid on it.
    $term = $contract->terms()->firstOrFail();
    foreach (['invoice', 'receipt', 'voucher'] as $kind) {
        $this->get($this->world->url("/print/project-contract/term/{$kind}/{$term->id}"))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
    $unpaid = $contract->terms()->create(['branch_id' => $contract->branch_id, 'label' => 'Later term', 'due_date' => '2027-01-01', 'amount' => 5000, 'created_by' => 1, 'updated_by' => 1]);
    $this->get($this->world->url("/print/project-contract/term/invoice/{$unpaid->id}"))->assertOk();
    $this->get($this->world->url("/print/project-contract/term/receipt/{$unpaid->id}"))->assertNotFound();

    $html = view('print.project-contract.term', [
        'term' => $term, 'contract' => $contract, 'kind' => 'invoice',
        'allocations' => $term->allocations()->with('payment.paymentMethod')->get(),
        'companyName' => 'Acme', 'companyAddress' => '', 'companyPhone' => '', 'companyEmail' => '', 'companyLogo' => null,
    ])->render();
    expect($html)->toContain('Invoice')->toContain('Amount Due')->toContain('20,000.00')->toContain('Paid So Far');
});

it('keeps another tenant\'s contracts out of reach', function (): void {
    $contract = ($this->activeContract)();

    $other = PosWorld::create();
    Configuration::create(['tenant_id' => $other->tenant->id, 'key' => 'active_module', 'value' => 'Agreement Module']);
    ($this->grant)($other->user);

    $this->actingAs($other->user)->withSession(['branch_id' => $other->branch->id])
        ->getJson($other->url("/project-contract/api/contracts/{$contract->id}"))->assertNotFound();
});
