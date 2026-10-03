<?php

use App\Livewire\RentOut\Tabs\NotesTab;
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
use Spatie\Permission\Models\Permission;

/**
 * Typing "@" in the rent-out note box lists users to pick from, and
 * "@Name" mentions are highlighted on the saved note.
 */
beforeEach(function (): void {
    $tenant = Tenant::create(['name' => 'NM Tenant', 'subdomain' => 'nm'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'N']);

    $makeUser = fn (string $name): User => User::create([
        'tenant_id' => $tenant->id, 'name' => $name, 'type' => 'employee', 'is_admin' => 1, 'is_active' => 1,
        'email' => 'nm'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);
    $this->author = $makeUser('NM Author');
    $this->alice = $makeUser('Alice Mention');
    $this->bob = $makeUser('Bob Mention');

    $customer = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'NM Customer']);
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'NM Branch', 'code' => 'NM']);
    session(['branch_id' => $branch->id]);
    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'NM Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id, 'name' => 'NM Building']);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'NM Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'NM-101',
    ]);
    $this->rentOut = RentOut::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $this->author->id, 'property_id' => $property->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(), 'created_by' => $this->author->id,
    ]);

    $this->actingAs($this->author);
    $this->author->givePermissionTo(Permission::findOrCreate('rent out note.create', 'web'));
});

it('lists users for the @ mention picker', function (): void {
    Livewire::test(NotesTab::class, ['rentOutId' => $this->rentOut->id])
        ->assertSee('Alice Mention')
        ->assertSee('Bob Mention');
});

it('highlights @mentioned user names in saved notes', function (): void {
    Livewire::test(NotesTab::class, ['rentOutId' => $this->rentOut->id])
        ->set('newNote', 'Please call the tenant @Alice Mention')
        ->call('addNote')
        ->assertSet('newNote', '')
        ->assertSeeHtml('<span class="badge bg-primary-subtle text-primary fw-semibold">@Alice Mention</span>');
});

it('highlights the longest matching name once, case-insensitively', function (): void {
    User::create([
        'tenant_id' => $this->author->tenant_id, 'name' => 'Alice', 'type' => 'employee', 'is_active' => 1,
        'email' => 'nm'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);

    Livewire::test(NotesTab::class, ['rentOutId' => $this->rentOut->id])
        ->set('newNote', 'cc @alice mention')
        ->call('addNote')
        ->assertSeeHtml('cc <span class="badge bg-primary-subtle text-primary fw-semibold">@alice mention</span>');
});
