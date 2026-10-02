<?php

use App\Livewire\RentOut\View;
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
 * Management Sections tabs are deep-linkable like the settings page:
 * `?tab=<slug>` opens a tab and switching tabs rewrites the query string.
 */
it('maps every management tab to a url slug', function (): void {
    $tenant = Tenant::create(['name' => 'MT Tenant', 'subdomain' => 'mt'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'M']);

    $user = User::create([
        'tenant_id' => $tenant->id, 'name' => 'MT User', 'type' => 'employee', 'is_admin' => 1,
        'email' => 'mt'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'MT Customer']);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'MT Branch', 'code' => 'MT']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'MT Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'MT Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'MT Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'MT-101',
    ]);
    $rentOut = RentOut::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $user->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(), 'created_by' => $user->id,
    ]);

    $this->actingAs($user);

    $html = Livewire::test(View::class, ['id' => $rentOut->id, 'agreementType' => 'lease'])->html();

    expect($html)
        ->toContain("url.searchParams.set('tab'")
        ->toContain("new URLSearchParams(window.location.search).get('tab')");

    preg_match("/tabSlugs: JSON\.parse\('(.+?)'\)/", $html, $match);
    $slugs = json_decode(json_decode('"'.$match[1].'"'), true);

    expect($slugs)->toMatchArray([
        'payment' => 'PaymentTab',
        'terms' => 'PaymentTermTab',
        'services' => 'ServicesTab',
        'cheques' => 'ChequeTab',
        'checklist' => 'ChecklistTab',
    ])->not->toHaveKey('utilities');
});
