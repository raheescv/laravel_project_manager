<?php

use App\Livewire\RentOut\Page;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The booking / agreement editor is a "Summary Rail" form: numbered sections
 * on the left, a live unit + money summary and every action on the right.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('renders the sale booking editor with its sections and summary rail', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->assertSee('New Sale Booking')
        ->assertSeeInOrder(['Property &amp; Customer', 'Sale Details', 'Down Payment &amp; Collection', 'Remarks &amp; Terms'], false)
        ->assertSee('Unit details')
        ->assertSee('Select a unit to see its details')
        ->assertDontSee('Contract total')
        ->assertDontSee('Included amenities');
});

it('shows the selected unit details on a sale instead of the contract total', function (): void {
    $tenantId = $this->world->tenant->id;
    $branchId = $this->world->branch->id;
    $group = PropertyGroup::create(['tenant_id' => $tenantId, 'name' => 'UD Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'property_group_id' => $group->id, 'name' => 'UD Tower']);
    $type = PropertyType::create(['tenant_id' => $tenantId, 'name' => 'Penthouse']);
    $property = Property::create([
        'tenant_id' => $tenantId, 'branch_id' => $branchId, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'UD-901',
        'floor' => '9', 'size' => 145.5, 'rooms' => '3', 'parking' => 'P2-14', 'kahramaa' => 'KH-7788',
    ]);

    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->set('rent_outs.property_id', $property->id)
        ->assertSeeInOrder(['Unit details', 'Type', 'Penthouse', 'Floor', '9', 'Size', '145.50 sq.m', 'Rooms', '3', 'Parking', 'P2-14', 'Kahramaa no.', 'KH-7788'])
        ->assertDontSee('Gas meter no.')
        ->assertDontSee('Contract total');
});

it('keeps the contract total card on a rental', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'rental'])
        ->set('rent_outs.rent', 1000)
        ->set('rent_outs.no_of_terms', 12)
        ->assertSet('rent_outs.total', 12000)
        ->assertSeeInOrder(['Contract total', '12,000.00', 'Terms', '12'])
        ->assertDontSee('Unit details');
});

it('picks frequency and payment mode with a tap and only asks for bank details off cash', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->assertDontSee('Cheque starting no.')
        ->call('$set', 'rent_outs.payment_frequency', 'Quarterly')
        ->assertSet('rent_outs.payment_frequency', 'Quarterly')
        ->call('$set', 'rent_outs.collection_payment_mode', 'cheque')
        ->assertSee('Bank name')
        ->assertSee('Cheque starting no.');
});

it('shows rental-only booking type and amenity toggles on a rental booking', function (): void {
    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'rental'])
        ->assertSee('New Rental Booking')
        ->assertSee('Included amenities')
        ->assertSee('Monthly Collection')
        ->assertDontSee('Down Payment &amp; Collection', false)
        ->call('$set', 'rent_outs.include_wifi', 'Excluded')
        ->assertSee('WiFi: Excluded');
});
