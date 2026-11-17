<?php

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Imports\PropertyImport;
use App\Livewire\RentOut\Page;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Services\TenantService;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * A unit carries its utility references — Kahramaa, gas meter, water and BTU —
 * from the import sheet through to the booking editor's unit details.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);

    $tenantId = $this->world->tenant->id;
    $this->group = PropertyGroup::create(['tenant_id' => $tenantId, 'name' => 'UN Group']);
    $this->building = PropertyBuilding::create([
        'tenant_id' => $tenantId, 'branch_id' => $this->world->branch->id,
        'property_group_id' => $this->group->id, 'name' => 'UN Tower',
    ]);
    $this->type = PropertyType::create(['tenant_id' => $tenantId, 'name' => 'UN 1 BHK']);
});

it('imports the water, BTU and gas meter numbers of a unit', function (): void {
    Event::fake([FileImportProgress::class, FileImportCompleted::class]);

    $mappings = [
        'number' => 'number',
        'property_building_id' => 'building',
        'property_type_id' => 'type',
        'gas_meter_number' => 'gas_meter_no',
        'water_number' => 'water_no',
        'btu_number' => 'btu_no',
    ];
    $rows = collect([collect([
        'number' => 'UN-807', 'building' => 'UN Tower', 'type' => 'UN 1 BHK',
        'gas_meter_no' => 'GM-807', 'water_no' => 'WM-807', 'btu_no' => 'BTU-807',
    ])]);

    // The queued import job runs with the uploader's tenant selected.
    app(TenantService::class)->setCurrentTenant($this->world->tenant);
    $import = new PropertyImport($this->world->user->id, 1, $this->world->branch->id, $mappings);
    $import->collection($rows);

    expect(array_column((fn () => $this->errors)->call($import), 'message'))->toBe([]);

    $property = Property::where('number', 'UN-807')->firstOrFail();
    expect($property->property_building_id)->toBe($this->building->id)
        ->and($property->gas_meter_number)->toBe('GM-807')
        ->and($property->water_number)->toBe('WM-807')
        ->and($property->btu_number)->toBe('BTU-807');
});

it('shows the water and BTU numbers in the booking unit details', function (): void {
    $property = Property::create([
        'tenant_id' => $this->world->tenant->id, 'branch_id' => $this->world->branch->id,
        'property_group_id' => $this->group->id, 'property_building_id' => $this->building->id,
        'property_type_id' => $this->type->id, 'number' => 'UN-808',
        'kahramaa' => 'KH-808', 'gas_meter_number' => 'GM-808', 'water_number' => 'WM-808', 'btu_number' => 'BTU-808',
    ]);

    Livewire::test(Page::class, ['type' => 'Booking', 'agreementType' => 'lease'])
        ->set('rent_outs.property_id', $property->id)
        ->assertSeeInOrder(['Kahramaa no.', 'KH-808', 'Gas meter no.', 'GM-808', 'Water no.', 'WM-808', 'BTU no.', 'BTU-808']);
});
