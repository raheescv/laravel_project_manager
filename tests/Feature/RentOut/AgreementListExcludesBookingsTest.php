<?php

use App\Enums\RentOut\RentOutStatus;
use App\Exports\RentOut\RentOutTableExport;
use App\Livewire\RentOut\Table;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Livewire\Livewire;

/**
 * Bookings have their own Booking page, so the Sale / Rent agreement lists
 * (and their export) must never show a RentOut that is still booked.
 */
beforeEach(function (): void {
    $tenant = Tenant::create(['name' => 'BX Tenant', 'subdomain' => 'bx'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'B']);

    $this->user = User::create([
        'tenant_id' => $tenant->id, 'name' => 'BX User', 'type' => 'employee', 'is_admin' => 1,
        'email' => 'bx'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'BX Customer']);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'BX Branch', 'code' => 'BX']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'BX Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'BX Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'BX Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'BX-101',
    ]);

    $makeRentOut = fn (string $status): RentOut => RentOut::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $this->user->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'status' => $status, 'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
        'created_by' => $this->user->id,
    ]);

    $this->agreement = $makeRentOut(RentOutStatus::Occupied->value);
    $this->booking = $makeRentOut(RentOutStatus::Booked->value);

    $this->actingAs($this->user);
});

it('lists only non-booked agreements on the sale list', function (): void {
    $ids = Livewire::test(Table::class, ['agreementType' => 'lease'])
        ->viewData('data')
        ->pluck('id');

    expect($ids)->toContain($this->agreement->id)->not->toContain($this->booking->id);
});

it('does not select bookings with select all', function (): void {
    Livewire::test(Table::class, ['agreementType' => 'lease'])
        ->set('selectAll', true)
        ->assertSet('selected', [$this->agreement->id]);
});

it('leaves bookings out of the sale list export', function (): void {
    $ids = (new RentOutTableExport(['agreementType' => 'lease']))->query()->pluck('id');

    expect($ids)->toContain($this->agreement->id)->not->toContain($this->booking->id);
});

it('keeps cancelled bookings off the sale list but shows cancelled agreements', function (): void {
    $cancelledBooking = RentOut::create(array_merge(
        $this->agreement->only(['tenant_id', 'branch_id', 'account_id', 'salesman_id', 'property_id', 'property_building_id', 'property_type_id', 'property_group_id', 'agreement_type', 'start_date', 'end_date', 'created_by']),
        ['status' => RentOutStatus::Cancelled->value, 'booking_status' => 'created'],
    ));
    $this->agreement->update(['status' => RentOutStatus::Cancelled->value]);

    $ids = Livewire::test(Table::class, ['agreementType' => 'lease'])->viewData('data')->pluck('id');

    expect($ids)->toContain($this->agreement->id)->not->toContain($cancelledBooking->id);
});
