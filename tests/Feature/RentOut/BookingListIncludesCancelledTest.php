<?php

use App\Enums\RentOut\RentOutStatus;
use App\Exports\RentOut\BookingExport;
use App\Livewire\RentOut\BookingTable;
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
 * The Booking page lists live bookings and cancelled bookings (status filterable),
 * while cancelled agreements (no booking_status) stay off it.
 */
beforeEach(function (): void {
    $tenant = Tenant::create(['name' => 'BK Tenant', 'subdomain' => 'bk'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'B']);

    $this->user = User::create([
        'tenant_id' => $tenant->id, 'name' => 'BK User', 'type' => 'employee', 'is_admin' => 1,
        'email' => 'bk'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'BK Customer']);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'BK Branch', 'code' => 'BK']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'BK Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'BK Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'BK Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'BK-101',
    ]);

    $makeRentOut = fn (string $status, ?string $bookingStatus = null): RentOut => RentOut::create([
        'booking_status' => $bookingStatus,
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $this->user->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'status' => $status, 'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
        'created_by' => $this->user->id,
    ]);

    $this->booking = $makeRentOut(RentOutStatus::Booked->value, 'created');
    $this->cancelledBooking = $makeRentOut(RentOutStatus::Cancelled->value, 'submitted');
    $this->cancelledAgreement = $makeRentOut(RentOutStatus::Cancelled->value);
    $this->agreement = $makeRentOut(RentOutStatus::Occupied->value);

    $this->actingAs($this->user);
});

it('lists booked and cancelled bookings but not agreements', function (): void {
    $ids = Livewire::test(BookingTable::class, ['agreementType' => 'lease'])
        ->viewData('data')
        ->pluck('id');

    expect($ids)->toContain($this->booking->id, $this->cancelledBooking->id)
        ->not->toContain($this->agreement->id)
        ->not->toContain($this->cancelledAgreement->id);
});

it('filters bookings by status', function (): void {
    $ids = Livewire::test(BookingTable::class, ['agreementType' => 'lease'])
        ->set('filterStatus', RentOutStatus::Cancelled->value)
        ->viewData('data')
        ->pluck('id');

    expect($ids->all())->toBe([$this->cancelledBooking->id]);
});

it('toggles the status column', function (): void {
    Livewire::test(BookingTable::class, ['agreementType' => 'lease'])
        ->assertSet('columns.status', true)
        ->assertSee('badge bg-secondary', false)
        ->call('toggleColumn', 'status')
        ->assertSet('columns.status', false);
});

it('exports cancelled bookings and honours the status filter', function (): void {
    $all = (new BookingExport(['agreementType' => 'lease']))->query()->pluck('id');
    $booked = (new BookingExport(['agreementType' => 'lease', 'filterStatus' => 'booked']))->query()->pluck('id');

    expect($all)->toContain($this->booking->id, $this->cancelledBooking->id)->not->toContain($this->cancelledAgreement->id)
        ->and($booked->all())->toBe([$this->booking->id]);
});
