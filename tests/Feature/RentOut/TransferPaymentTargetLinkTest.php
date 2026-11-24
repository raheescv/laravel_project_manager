<?php

use App\Livewire\RentOut\Tabs\TransferPaymentModal;
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
 * The selected "Transfer To" chip links to the target agreement's view page,
 * routed by its agreement type (rental → rent::view, lease → sale::view).
 */
it('links the selected transfer target to its agreement view page', function (string $agreementType, string $routeName): void {
    $tenant = Tenant::create(['name' => 'TP Tenant', 'subdomain' => 'tp'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'T']);

    $user = User::create([
        'tenant_id' => $tenant->id, 'name' => 'TP User', 'type' => 'employee', 'is_admin' => 1,
        'email' => 'tp'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'TP Customer']);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'TP Branch', 'code' => 'TP']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'TP Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'TP Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'TP Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'TP-101',
    ]);
    $target = RentOut::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $user->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => $agreementType,
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(), 'created_by' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(TransferPaymentModal::class)
        ->assertDontSee(route($routeName, $target->id))
        ->call('selectTarget', $target->id, '#'.$target->id.' · TP Building')
        ->assertSee(route($routeName, $target->id))
        ->call('clearTarget')
        ->assertDontSee(route($routeName, $target->id));
})->with([
    'rental' => ['rental', 'property::rent::view'],
    'lease' => ['lease', 'property::sale::view'],
]);
