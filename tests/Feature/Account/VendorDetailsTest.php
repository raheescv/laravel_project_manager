<?php

use App\Livewire\Account\Vendor\Page;
use App\Livewire\Account\Vendor\Table;
use App\Models\Account;
use App\Models\ProjectContract;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The Account → Vendors screen: its create / edit modal carries the full
 * vendor details, and deleting refuses a vendor that is in use.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    foreach (['view', 'create', 'edit', 'delete'] as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "vendor.{$action}", 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('creates a vendor with the full details from the modal', function (): void {
    Livewire::test(Page::class)
        ->set('accounts.name', 'Gulf Steel Contracting')
        ->set('accounts.alias_name', 'الخليج للمقاولات')
        ->set('accounts.vendor_type', 'subcontractor')
        ->set('accounts.mobile', '55512345')
        ->set('accounts.cr_number', 'CR 128844')
        ->set('accounts.nationality', 'Qatar')
        ->set('accounts.passport_no', 'P998877')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $vendor = Account::vendor()->where('name', 'Gulf Steel Contracting')->firstOrFail();
    expect($vendor->account_type)->toBe('liability')
        ->and($vendor->alias_name)->toBe('الخليج للمقاولات')
        ->and($vendor->vendor_type)->toBe('subcontractor')
        ->and($vendor->whatsapp_mobile)->toBe('55512345')
        ->and($vendor->cr_number)->toBe('CR 128844')
        ->and($vendor->passport_no)->toBe('P998877')
        ->and($vendor->nationality)->toBe('Qatar');
});

it('loads a vendor into the modal for editing and saves the changes', function (): void {
    $vendor = Account::create(['account_type' => 'liability', 'model' => 'Vendor', 'name' => 'CoolAir Systems', 'mobile' => '55000111', 'passport_no' => 'P1234']);

    Livewire::test(Page::class)
        ->call('edit', $vendor->id)
        ->assertSet('accounts.name', 'CoolAir Systems')
        ->assertSet('showMore', true)
        ->set('accounts.vendor_type', 'company')
        ->set('accounts.email', 'not-an-email')
        ->call('save')
        ->assertHasErrors('accounts.email')
        ->set('accounts.email', 'projects@coolair.qa')
        ->call('save')
        ->assertHasNoErrors();

    expect($vendor->fresh()->vendor_type)->toBe('company')
        ->and($vendor->fresh()->email)->toBe('projects@coolair.qa');
});

it('deletes a vendor from its row but keeps one that has contracts', function (): void {
    $free = Account::create(['account_type' => 'liability', 'model' => 'Vendor', 'name' => 'Unused Vendor', 'mobile' => '1']);
    $used = Account::create(['account_type' => 'liability', 'model' => 'Vendor', 'name' => 'Busy Vendor', 'mobile' => '2']);
    ProjectContract::factory()->create(['account_id' => $used->id, 'branch_id' => $this->world->branch->id]);

    Livewire::test(Table::class)->call('deleteOne', $used->id)->assertDispatched('error');
    Livewire::test(Table::class)->call('deleteOne', $free->id)->assertDispatched('success');

    expect(Account::find($used->id))->not->toBeNull()
        ->and(Account::find($free->id))->toBeNull();
});
