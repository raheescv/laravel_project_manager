<?php

use App\Livewire\Property\Property\View;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::findOrCreate('property.view', 'web'));
    $this->actingAs($this->world->user->fresh());
    session(['branch_id' => $this->world->branch->id]);
});

it('lists changed fields even when an audited decimal value is not numeric', function (): void {
    $tenantId = $this->world->tenant->id;
    $group = PropertyGroup::create(['tenant_id' => $tenantId, 'name' => 'PV Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenantId, 'branch_id' => $this->world->branch->id, 'property_group_id' => $group->id, 'name' => 'PV Building']);
    $type = PropertyType::create(['tenant_id' => $tenantId, 'name' => 'PV Type']);
    $property = Property::create([
        'tenant_id' => $tenantId, 'branch_id' => $this->world->branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'PV-101',
    ]);

    $property->audits()->create([
        'event' => 'updated',
        'user_type' => User::class,
        'user_id' => $this->world->user->id,
        'old_values' => ['size' => '', 'rent' => 'n/a'],
        'new_values' => ['size' => '120', 'rent' => '4500'],
    ]);

    Livewire::test(View::class, ['id' => $property->id])
        ->assertOk()
        ->assertSee('— size, rent');
});
