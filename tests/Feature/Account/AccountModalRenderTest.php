<?php

use App\Livewire\Account\Page;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The Create/Edit Account modal renders the "Compact Flow" layout: a type chip
 * row (hidden when the caller freezes the type), Dr/Cr opening balance and footer actions.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('renders the compact flow rows with a type chip per account type', function (): void {
    $component = Livewire::test(Page::class)
        ->call('create')
        ->assertSee('Create Account')
        ->assertSeeHtml('class="acctx-types"')
        ->assertSeeHtml('id="opening_debit"')
        ->assertSeeHtml('id="opening_credit"')
        ->assertSee('Save & New');

    foreach (array_keys(accountTypes()) as $type) {
        $component->assertSeeHtml('value="'.$type.'"');
    }
});

it('hides the type chips when the type is frozen by the caller', function (): void {
    Livewire::test(Page::class)
        ->call('create', 'Bank Charges', 'expense')
        ->assertSet('type_selection_freeze', true)
        ->assertDontSeeHtml('class="acctx-types"');
});

it('titles the modal Edit Account for an existing account', function (): void {
    Livewire::test(Page::class)
        ->call('edit', $this->world->cashAccountId)
        ->assertSee('Edit Account')
        ->assertSee('Cheque Account');
});
