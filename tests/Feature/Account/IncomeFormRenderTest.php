<?php

use App\Livewire\Account\Income\Page;
use App\Models\Configuration;
use App\Models\Journal;
use App\Models\JournalEntry;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Accounts → Income runs on the same Premium (.exx) "Flow" form as Expense,
 * with the journal legs mirrored: the category is the CREDIT (income) account
 * and the payment method is the DEBIT (received-in) account.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => 'default_payment_method_id',
        'value' => $this->world->cashAccountId,
    ]);

    foreach (['income.create', 'income.edit'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('renders the new-income form with the default payment method as the debit leg', function (): void {
    Livewire::test(Page::class)
        ->assertOk()
        ->assertSee('Record Income')
        ->assertSee('Received in')
        ->assertSee('Save & Add New')
        ->assertSet('journals.debit', $this->world->cashAccountId)
        ->assertSet('journals.debit_name', 'Cash')
        ->assertSet('journals.credit', null)
        ->assertSet('recentCategories', []);
});

it('shows every validation message on an empty save', function (): void {
    Livewire::test(Page::class)
        ->call('save')
        ->assertHasErrors(['journals.credit', 'journals.amount', 'journals.description'])
        ->assertSee('The Payment Method field is required.')
        ->assertSee('The Amount must be at least 1.')
        ->assertSee('The Description field is required.');
});

it('offers recently used income accounts as chips', function (): void {
    $sale = $this->world->accounts['sale'];
    $journal = Journal::create([
        'branch_id' => $this->world->branch->id,
        'date' => today()->toDateString(),
        'description' => 'Alteration charges',
        'source' => 'income',
        'created_by' => $this->world->user->id,
    ]);
    JournalEntry::create([
        'journal_id' => $journal->id,
        'branch_id' => $this->world->branch->id,
        'account_id' => $sale,
        'counter_account_id' => $this->world->cashAccountId,
        'date' => today()->toDateString(),
        'debit' => 0,
        'credit' => 60,
        'source' => 'income',
        'created_by' => $this->world->user->id,
    ]);

    Livewire::test(Page::class)
        ->assertSet('recentCategories', [['id' => $sale, 'name' => 'Sale']])
        ->assertSee('Sale');
});

it('records an income from the form', function (): void {
    $sale = $this->world->accounts['sale'];

    Livewire::test(Page::class)
        ->set('journals.credit', $sale)
        ->set('journals.amount', 60)
        ->set('journals.description', 'Alteration charges')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('RefreshIncomeTable');

    expect(JournalEntry::income()->where('account_id', $sale)->where('credit', 60)->exists())->toBeTrue()
        ->and(JournalEntry::income()->where('account_id', $this->world->cashAccountId)->where('debit', 60)->exists())->toBeTrue();
});
