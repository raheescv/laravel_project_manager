<?php

use App\Actions\RentOut\GenerateResidentialLeaseAction;
use App\Enums\RentOut\AgreementType;
use App\Livewire\Property\Building\Page as BuildingPage;
use App\Models\Account;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\RentOut;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Rental lease lessee and premises blocks, carried over from the accounts app:
 * the lessee's CR No, meter rows only when the unit has that meter, and the
 * building's Property Type No.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

/**
 * @return array<int, array{english: array<string, mixed>, arabic: array<string, mixed>}>
 */
function leaseRows(string $method, RentOut $rentOut): array
{
    $action = new GenerateResidentialLeaseAction();

    return (fn () => $this->{$method}($rentOut))->call($action);
}

/**
 * @return list<string>
 */
function leaseRowTitles(array $rows): array
{
    return array_map(fn (array $row): string => $row['english']['title'], $rows);
}

function leaseRentOut(array $property = [], array $building = [], ?Account $customer = null): RentOut
{
    $rentOut = new RentOut(['agreement_type' => AgreementType::Rental]);
    $rentOut->setRelation('property', new Property(array_merge(['number' => '1203', 'kahramaa' => 'K-1'], $property)));
    $rentOut->setRelation('building', new PropertyBuilding(array_merge(['name' => 'MARINA TOWER'], $building)));
    $rentOut->setRelation('customer', $customer ?? new Account(['name' => 'Ali Hassan', 'id_no' => '28563400123']));

    return $rentOut;
}

it('prints the lessee CR No after the QID only when the customer has one', function (): void {
    $withCr = leaseRows('buildLesseeData', leaseRentOut(customer: new Account(['name' => 'Gulf Co', 'id_no' => '285', 'cr_number' => '102542'])));
    $withoutCr = leaseRows('buildLesseeData', leaseRentOut());

    expect(leaseRowTitles($withCr))->toBe(['Name', 'Qid No', 'CR No', 'Mobile No', 'E-Mail'])
        ->and($withCr[2]['english']['value'])->toBe('102542')
        ->and($withCr[2]['english']['keep_original'])->toBeTrue()
        ->and(leaseRowTitles($withoutCr))->toBe(['Name', 'Qid No', 'Mobile No', 'E-Mail']);
});

it('prints water, BTU and gas meter rows only when the unit has that meter', function (): void {
    $allMeters = leaseRows('buildPremisesDetails', leaseRentOut(['water_number' => 'W-9', 'btu_number' => 'B-4', 'gas_meter_number' => 'G-2']));
    $waterOnly = leaseRows('buildPremisesDetails', leaseRentOut(['water_number' => 'W-9', 'btu_number' => '', 'gas_meter_number' => null]));

    expect(leaseRowTitles($allMeters))->toBe([
        'Tower', 'Apartment type', 'Unit No', 'Electricity No', 'Water No', 'BTU No', 'Gas Meter No', 'Property Type No', 'Parking Number',
    ])
        ->and(leaseRowTitles($waterOnly))->toBe([
            'Tower', 'Apartment type', 'Unit No', 'Electricity No', 'Water No', 'Property Type No', 'Parking Number',
        ]);
});

it('prints the building property type no in the premises block', function (): void {
    $rows = collect(leaseRows('buildPremisesDetails', leaseRentOut(building: ['property_type_no' => 'PT-77'])))
        ->keyBy(fn (array $row): string => $row['english']['title']);

    expect($rows['Property Type No']['english']['value'])->toBe('PT-77')
        ->and($rows['Property Type No']['arabic']['title'])->toBe('رقم نوع العقار');
});

it('saves the property type no from the building form', function (): void {
    $this->world->user->givePermissionTo(Permission::findOrCreate('property building.create', 'web'));
    $group = PropertyGroup::create(['tenant_id' => $this->world->tenant->id, 'branch_id' => $this->world->branch->id, 'name' => 'Marina']);

    Livewire::test(BuildingPage::class)
        ->set('formData.name', 'Tower A')
        ->set('formData.property_group_id', $group->id)
        ->set('formData.ownership', 'own')
        ->set('formData.property_type_no', 'PT-12')
        ->call('save')
        ->assertHasNoErrors();

    expect(PropertyBuilding::where('name', 'Tower A')->value('property_type_no'))->toBe('PT-12');
});
