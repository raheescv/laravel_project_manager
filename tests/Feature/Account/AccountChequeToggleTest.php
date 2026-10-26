<?php

use App\Livewire\Account\Page;
use App\Models\Account;
use App\Models\AccountCategory;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The Edit Account modal exposes the "Cheque Account" switch for asset
 * accounts; it persists, and is forced off for any non-asset type.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('loads, renders and saves the cheque flag when editing an asset account', function (): void {
    $cashId = $this->world->cashAccountId;

    Livewire::test(Page::class)
        ->call('edit', $cashId)
        ->assertSet('accounts.account_type', 'asset')
        ->assertSet('accounts.is_cheque', false)
        ->assertSee('Cheque Account')
        ->set('accounts.is_cheque', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Account::find($cashId)->is_cheque)->toBeTrue();

    Livewire::test(Page::class)
        ->call('edit', $cashId)
        ->assertSet('accounts.is_cheque', true)
        ->set('accounts.is_cheque', false)
        ->call('save');

    expect(Account::find($cashId)->is_cheque)->toBeFalse();
});

it('forces the cheque flag off for non-asset accounts', function (): void {
    $saleId = $this->world->accounts['sale'];

    Livewire::test(Page::class)
        ->call('edit', $saleId)
        ->set('accounts.is_cheque', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Account::find($saleId)->is_cheque)->toBeFalse();
});

it('resets the account category after save & add new so the dropdown clears', function (): void {
    $category = AccountCategory::factory()->create();

    Livewire::test(Page::class)
        ->call('create')
        ->set('accounts.name', 'Petty Cash Drawer')
        ->set('accounts.account_type', 'asset')
        ->set('accounts.account_category_id', $category->id)
        ->call('save', true)
        ->assertHasNoErrors()
        ->assertSet('accounts.account_category_id', null)
        ->assertDispatched('SelectDropDownValues', fn (string $event, array $params): bool => empty($params[0]['account_category_id']));

    expect(Account::where('name', 'Petty Cash Drawer')->value('account_category_id'))->toBe($category->id);
});
