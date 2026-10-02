<?php

use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use Tests\Support\PosWorld;

/**
 * The rent-out Type dropdown narrows to the types that actually exist in the
 * chosen group / building.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

function typeDropDownUnit(PropertyGroup $group, PropertyBuilding $building, PropertyType $type): Property
{
    return Property::create([
        'tenant_id' => test()->world->tenant->id,
        'branch_id' => test()->world->branch->id,
        'property_group_id' => $group->id,
        'property_building_id' => $building->id,
        'property_type_id' => $type->id,
        'number' => 'U-'.uniqid(),
    ]);
}

it('limits property types to the selected group and building', function (): void {
    $tenantId = $this->world->tenant->id;
    $groupA = PropertyGroup::create(['tenant_id' => $tenantId, 'name' => 'Group A']);
    $groupB = PropertyGroup::create(['tenant_id' => $tenantId, 'name' => 'Group B']);
    $buildingA1 = PropertyBuilding::create(['tenant_id' => $tenantId, 'property_group_id' => $groupA->id, 'name' => 'A1']);
    $buildingA2 = PropertyBuilding::create(['tenant_id' => $tenantId, 'property_group_id' => $groupA->id, 'name' => 'A2']);
    $buildingB1 = PropertyBuilding::create(['tenant_id' => $tenantId, 'property_group_id' => $groupB->id, 'name' => 'B1']);
    $oneBed = PropertyType::create(['tenant_id' => $tenantId, 'name' => '1 Bedroom']);
    $twoBed = PropertyType::create(['tenant_id' => $tenantId, 'name' => '2 Bedroom']);
    $villa = PropertyType::create(['tenant_id' => $tenantId, 'name' => 'Villa']);

    typeDropDownUnit($groupA, $buildingA1, $oneBed);
    typeDropDownUnit($groupA, $buildingA2, $twoBed);
    typeDropDownUnit($groupB, $buildingB1, $villa);

    $names = fn (array $params): array => collect((new PropertyType())->getDropDownList($params)['items'])->pluck('name')->all();

    expect($names([]))->toBe(['1 Bedroom', '2 Bedroom', 'Villa'])
        ->and($names(['property_group_id' => $groupA->id]))->toBe(['1 Bedroom', '2 Bedroom'])
        ->and($names(['property_group_id' => $groupA->id, 'property_building_id' => $buildingA2->id]))->toBe(['2 Bedroom'])
        ->and($names(['building_id' => $buildingB1->id]))->toBe(['Villa']);
});
