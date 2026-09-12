<?php

use App\Livewire\Account\Expense\Page;
use App\Models\Configuration;
use App\Models\Journal;
use App\Models\JournalEntry;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Accounts → Expense runs on the Premium (.exx) "Flow" form. These keep the
 * redesign honest: the modal renders, validation still lands on every field,
 * the recently-used category chips are fed, and a save still posts the journal.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => 'default_payment_method_id',
        'value' => $this->world->cashAccountId,
    ]);

    foreach (['expense.create', 'expense.edit'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('renders the new-expense form with the default payment method', function (): void {
    Livewire::test(Page::class)
        ->assertOk()
        ->assertSee('Record Expense')
        ->assertSee('Save & Add New')
        ->assertSee('Paid from')
        ->assertSet('journals.credit', $this->world->cashAccountId)
        ->assertSet('journals.credit_name', 'Cash')
        ->assertSet('recentCategories', []);
});

it('shows every validation message on an empty save', function (): void {
    Livewire::test(Page::class)
        ->call('save')
        ->assertHasErrors(['journals.debit', 'journals.amount', 'journals.description'])
        ->assertSee('The Expense Category field is required.')
        ->assertSee('The Amount must be at least 1.')
        ->assertSee('The Description field is required.');
});

it('offers recently used expense accounts as chips', function (): void {
    $freight = $this->world->accounts['freight'];
    $journal = Journal::create([
        'branch_id' => $this->world->branch->id,
        'date' => today()->toDateString(),
        'description' => 'Courier',
        'source' => 'expense',
        'created_by' => $this->world->user->id,
    ]);
    JournalEntry::create([
        'journal_id' => $journal->id,
        'branch_id' => $this->world->branch->id,
        'account_id' => $freight,
        'counter_account_id' => $this->world->cashAccountId,
        'date' => today()->toDateString(),
        'debit' => 40,
        'credit' => 0,
        'source' => 'expense',
        'created_by' => $this->world->user->id,
    ]);

    Livewire::test(Page::class)
        ->assertSet('recentCategories', [['id' => $freight, 'name' => 'Freight']])
        ->assertSee('Freight');
});

it('records an expense from the form', function (): void {
    $freight = $this->world->accounts['freight'];

    Livewire::test(Page::class)
        ->set('journals.debit', $freight)
        ->set('journals.amount', 40)
        ->set('journals.description', 'Courier to client')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('RefreshExpenseTable');

    expect(JournalEntry::expense()->where('account_id', $freight)->where('debit', 40)->exists())->toBeTrue();
});
