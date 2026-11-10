<?php

use App\Livewire\Account\Customer\Page;
use App\Models\Account;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The Add / Edit Customer modal carries the CR and EID documents and who
 * authorized the customer.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('shows the CR, EID and authorized by inputs', function (string $field): void {
    Livewire::test(Page::class)->assertSeeHtml('wire:model="accounts.'.$field.'"');
})->with(['po_box', 'cr_number', 'cr_issue_date', 'cr_expiry_date', 'eid_number', 'eid_issue_date', 'eid_expiry_date', 'authorized_by']);

it('creates a customer with the CR, EID and authorized by details', function (): void {
    Livewire::test(Page::class)
        ->set('accounts.name', 'Doha Trading LLC')
        ->set('accounts.mobile', '55512345')
        ->set('accounts.entity_type', 'company')
        ->set('accounts.po_box', '4471')
        ->set('accounts.cr_number', 'CR-128844')
        ->set('accounts.cr_issue_date', '2025-01-15')
        ->set('accounts.cr_expiry_date', '2027-01-14')
        ->set('accounts.eid_number', 'EID-7788')
        ->set('accounts.eid_issue_date', '2024-03-01')
        ->set('accounts.eid_expiry_date', '2029-02-28')
        ->set('accounts.authorized_by', 'Hassan Ali')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $customer = Account::where('name', 'Doha Trading LLC')->firstOrFail();

    expect($customer->entity_type)->toBe('company')
        ->and($customer->po_box)->toBe('4471')
        ->and($customer->cr_number)->toBe('CR-128844')
        ->and($customer->cr_issue_date)->toBe('2025-01-15')
        ->and($customer->cr_expiry_date)->toBe('2027-01-14')
        ->and($customer->eid_number)->toBe('EID-7788')
        ->and($customer->eid_issue_date)->toBe('2024-03-01')
        ->and($customer->eid_expiry_date)->toBe('2029-02-28')
        ->and($customer->authorized_by)->toBe('Hassan Ali');
});

it('loads the details for editing and saves the changes', function (): void {
    $customer = Account::create([
        'account_type' => 'asset',
        'model' => 'customer',
        'name' => 'Pearl Holdings',
        'mobile' => '55000111',
        'email' => 'accounts@pearl.test',
        'cr_number' => 'CR-1',
        'eid_expiry_date' => '2028-06-30',
        'authorized_by' => 'Old Signatory',
    ]);

    Livewire::test(Page::class)
        ->call('edit', $customer->id)
        ->assertSet('accounts.cr_number', 'CR-1')
        ->assertSet('accounts.eid_expiry_date', '2028-06-30')
        ->assertSet('accounts.authorized_by', 'Old Signatory')
        ->set('accounts.cr_number', 'CR-2')
        ->set('accounts.authorized_by', 'New Signatory')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    expect($customer->fresh()->cr_number)->toBe('CR-2')
        ->and($customer->fresh()->authorized_by)->toBe('New Signatory');
});

it('stores cleared details as null', function (): void {
    $customer = Account::create([
        'account_type' => 'asset',
        'model' => 'customer',
        'name' => 'Corniche Foods',
        'mobile' => '55000222',
        'email' => 'accounts@corniche.test',
        'po_box' => '9001',
        'cr_number' => 'CR-9',
        'cr_expiry_date' => '2027-05-01',
        'eid_issue_date' => '2024-05-01',
        'authorized_by' => 'Signatory',
    ]);

    Livewire::test(Page::class)
        ->call('edit', $customer->id)
        ->set('accounts.po_box', '')
        ->set('accounts.cr_number', '')
        ->set('accounts.cr_expiry_date', '')
        ->set('accounts.eid_issue_date', '')
        ->set('accounts.authorized_by', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $customer->refresh();

    expect($customer->po_box)->toBeNull()
        ->and($customer->cr_number)->toBeNull()
        ->and($customer->cr_expiry_date)->toBeNull()
        ->and($customer->eid_issue_date)->toBeNull()
        ->and($customer->authorized_by)->toBeNull();
});

it('hides only the CR inputs while individual is selected', function (): void {
    $crNumberInput = 'wire:model="accounts.cr_number"';
    $eidNumberInput = 'wire:model="accounts.eid_number"';

    Livewire::test(Page::class)
        ->assertSeeHtml('wire:model.live="accounts.entity_type"')
        ->assertSeeHtml($crNumberInput)
        ->assertSee('CR & EID Details')
        ->set('accounts.entity_type', 'individual')
        ->assertDontSeeHtml($crNumberInput)
        ->assertDontSeeHtml('wire:model="accounts.cr_issue_date"')
        ->assertDontSeeHtml('wire:model="accounts.cr_expiry_date"')
        ->assertSeeHtml($eidNumberInput)
        ->assertSee('EID Details')
        ->assertDontSee('CR & EID Details')
        ->set('accounts.entity_type', 'company')
        ->assertSeeHtml($crNumberInput)
        ->assertSeeHtml($eidNumberInput);
});

it('accepts only company or individual as the entity', function (string $entityType, bool $isValid): void {
    $component = Livewire::test(Page::class)
        ->set('accounts.name', 'Entity Check')
        ->set('accounts.mobile', '55000333')
        ->set('accounts.entity_type', $entityType)
        ->call('save');

    $isValid ? $component->assertHasNoErrors() : $component->assertHasErrors(['accounts.entity_type']);
})->with([
    'company' => ['company', true],
    'individual' => ['individual', true],
    'anything else' => ['partnership', false],
]);

it('keeps the existing customer type relation separate from the entity', function (): void {
    $customer = Account::create([
        'account_type' => 'asset',
        'model' => 'customer',
        'name' => 'Separate Fields',
        'mobile' => '55000444',
        'entity_type' => 'individual',
    ]);

    expect($customer->fresh()->toArray())->toHaveKey('entity_type', 'individual');
});

it('rejects invalid details', function (string $field, string $value, string $rule): void {
    Livewire::test(Page::class)
        ->set('accounts.'.$field, $value)
        ->call('save')
        ->assertHasErrors(['accounts.'.$field => $rule]);
})->with([
    'po box too long' => ['po_box', str_repeat('9', 21), 'max'],
    'cr number too long' => ['cr_number', str_repeat('9', 51), 'max'],
    'cr issue date' => ['cr_issue_date', 'not-a-date', 'date'],
    'cr expiry date' => ['cr_expiry_date', 'not-a-date', 'date'],
    'eid number too long' => ['eid_number', str_repeat('9', 51), 'max'],
    'eid issue date' => ['eid_issue_date', 'not-a-date', 'date'],
    'eid expiry date' => ['eid_expiry_date', 'not-a-date', 'date'],
    'authorized by too long' => ['authorized_by', str_repeat('a', 101), 'max'],
]);
