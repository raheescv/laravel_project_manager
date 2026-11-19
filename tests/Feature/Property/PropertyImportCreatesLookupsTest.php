<?php

use App\Enums\Property\PropertyStatus;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Imports\PropertyImport;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Services\TenantService;
use Illuminate\Support\Facades\Event;
use Tests\Support\PosWorld;

/**
 * A first property upload into an empty tenant creates the group, building and
 * type named on each row instead of rejecting every row as unresolved.
 */
beforeEach(function (): void {
    Event::fake([FileImportProgress::class, FileImportCompleted::class]);

    $this->world = PosWorld::create();
    app(TenantService::class)->setCurrentTenant($this->world->tenant);

    $this->mappings = [
        'number' => 'number',
        'property_building_id' => 'property_building',
        'property_type_id' => 'property_type',
        'property_group_id' => 'property_group',
        'ownership' => 'ownership',
        'rent' => 'rent',
    ];
});

function runPropertyImport(array $mappings, int $userId, int $branchId, array $rows): array
{
    $import = new PropertyImport($userId, count($rows), $branchId, $mappings);
    $import->collection(collect($rows)->map(fn (array $row) => collect($row)));

    return array_column((fn () => $this->errors)->call($import), 'message');
}

it('creates missing group, building and type once and links every unit to them', function (): void {
    $row = ['property_building' => 'IMP Khaled Compound', 'property_type' => 'IMP 2 BHK', 'property_group' => 'IMP Khaled Compound', 'ownership' => 'owned', 'rent' => 6500];

    $errors = runPropertyImport($this->mappings, $this->world->user->id, $this->world->branch->id, [
        ['number' => 'IMP-F1', ...$row],
        ['number' => 'IMP-F2', ...$row],
    ]);

    expect($errors)->toBe([]);

    $group = PropertyGroup::where('name', 'IMP Khaled Compound')->sole();
    $building = PropertyBuilding::where('name', 'IMP Khaled Compound')->sole();
    $type = PropertyType::where('name', 'IMP 2 BHK')->sole();

    expect($building->property_group_id)->toBe($group->id)
        ->and($building->branch_id)->toBe($this->world->branch->id)
        ->and($building->ownership->value)->toBe('own');

    $units = Property::whereIn('number', ['IMP-F1', 'IMP-F2'])->get();
    expect($units)->toHaveCount(2)
        ->and($units->pluck('property_building_id')->unique()->all())->toBe([$building->id])
        ->and($units->pluck('property_group_id')->unique()->all())->toBe([$group->id])
        ->and($units->pluck('property_type_id')->unique()->all())->toBe([$type->id]);
});

it('reuses existing records by name and takes the group from the building when none is given', function (): void {
    $group = PropertyGroup::create(['tenant_id' => $this->world->tenant->id, 'branch_id' => $this->world->branch->id, 'name' => 'IMP Existing Group']);
    $building = PropertyBuilding::create([
        'tenant_id' => $this->world->tenant->id, 'branch_id' => $this->world->branch->id,
        'property_group_id' => $group->id, 'name' => 'IMP Existing Tower',
    ]);
    $type = PropertyType::create(['tenant_id' => $this->world->tenant->id, 'name' => 'IMP Studio']);

    $errors = runPropertyImport($this->mappings, $this->world->user->id, $this->world->branch->id, [
        ['number' => 'IMP-S1', 'property_building' => ' imp existing tower ', 'property_type' => 'IMP STUDIO', 'property_group' => '', 'ownership' => 'owned', 'rent' => 3000],
    ]);

    expect($errors)->toBe([])
        ->and(PropertyBuilding::where('name', 'like', 'IMP Existing%')->count())->toBe(1)
        ->and(PropertyType::where('name', 'IMP Studio')->count())->toBe(1);

    $unit = Property::where('number', 'IMP-S1')->sole();
    expect($unit->property_building_id)->toBe($building->id)
        ->and($unit->property_type_id)->toBe($type->id)
        ->and($unit->property_group_id)->toBe($group->id);
});

it('still rejects a new building name when the row has no group to put it in', function (): void {
    $errors = runPropertyImport($this->mappings, $this->world->user->id, $this->world->branch->id, [
        ['number' => 'IMP-X1', 'property_building' => 'IMP Orphan Tower', 'property_type' => 'IMP 1 BHK', 'property_group' => '', 'ownership' => 'owned', 'rent' => 1000],
    ]);

    expect($errors)->toBe(['Building could not be resolved for row "IMP-X1".'])
        ->and(PropertyBuilding::where('name', 'IMP Orphan Tower')->exists())->toBeFalse();
});

it('accepts a status cell in any casing and leaves a blank one to the default', function (): void {
    $row = ['property_building' => 'IMP Status Tower', 'property_type' => 'IMP 3 BHK', 'property_group' => 'IMP Status Group', 'ownership' => 'owned', 'rent' => 4000];

    $errors = runPropertyImport([...$this->mappings, 'status' => 'status'], $this->world->user->id, $this->world->branch->id, [
        ['number' => 'IMP-T1', 'status' => 'Occupied', ...$row],
        ['number' => 'IMP-T2', 'status' => ' VACANT ', ...$row],
        ['number' => 'IMP-T3', 'status' => '', ...$row],
    ]);

    expect($errors)->toBe([]);

    $statuses = Property::whereIn('number', ['IMP-T1', 'IMP-T2', 'IMP-T3'])->get()->mapWithKeys(fn (Property $unit) => [$unit->number => $unit->fresh()->status]);
    expect($statuses['IMP-T1'])->toBe(PropertyStatus::Occupied)
        ->and($statuses['IMP-T2'])->toBe(PropertyStatus::Vacant)
        ->and($statuses['IMP-T3'])->toBe(PropertyStatus::Vacant);
});

it('reports an unknown status as a row error instead of crashing the upload', function (): void {
    $errors = runPropertyImport([...$this->mappings, 'status' => 'status'], $this->world->user->id, $this->world->branch->id, [
        ['number' => 'IMP-U1', 'status' => 'Rented', 'property_building' => 'IMP Status Tower', 'property_type' => 'IMP 3 BHK', 'property_group' => 'IMP Status Group', 'ownership' => 'owned', 'rent' => 4000],
    ]);

    expect($errors)->toBe(['Unknown status "Rented". Use one of: vacant, occupied, booked, sold.'])
        ->and(Property::where('number', 'IMP-U1')->exists())->toBeFalse();
});
