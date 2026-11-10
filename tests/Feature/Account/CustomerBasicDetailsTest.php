<?php

use App\Livewire\Account\Customer\BasicDetails;
use App\Models\Account;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The customer view's Basic Details tab shows the entity, authorized by, CR
 * and EID values, leaving the CR details off individual customers.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'customer kyc.view', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

function basicDetailsCustomer(string $entityType): Account
{
    return Account::create([
        'account_type' => 'asset',
        'model' => 'customer',
        'name' => 'Basic Details Customer',
        'mobile' => '55000555',
        'entity_type' => $entityType,
        'po_box' => '4471',
        'authorized_by' => 'Hassan Ali',
        'cr_number' => 'CR-128844',
        'cr_issue_date' => '2025-01-15',
        'cr_expiry_date' => '2027-01-14',
        'eid_number' => 'EID-7788',
        'eid_issue_date' => '2024-03-01',
        'eid_expiry_date' => '2029-02-28',
    ]);
}

it('shows the new details for a company customer', function (): void {
    Livewire::test(BasicDetails::class, ['account_id' => basicDetailsCustomer('company')->id])
        ->assertSee('Company')
        ->assertSee('4471')
        ->assertSee('Hassan Ali')
        ->assertSee('Commercial Registration')
        ->assertSee('CR-128844')
        ->assertSee(systemDate('2025-01-15'))
        ->assertSee(systemDate('2027-01-14'))
        ->assertSee('EID-7788')
        ->assertSee(systemDate('2024-03-01'))
        ->assertSee(systemDate('2029-02-28'))
        ->assertViewHas('documents', fn ($documents): bool => $documents->has('CR') && $documents->has('EID'));
});

it('leaves the CR details off an individual customer', function (): void {
    Livewire::test(BasicDetails::class, ['account_id' => basicDetailsCustomer('individual')->id])
        ->assertSee('Individual')
        ->assertSee('Hassan Ali')
        ->assertSee('EID-7788')
        ->assertDontSee('Commercial Registration')
        ->assertDontSee('CR-128844')
        ->assertDontSee(systemDate('2027-01-14'))
        ->assertViewHas('documents', fn ($documents): bool => ! $documents->has('CR') && $documents->has('EID'));
});
