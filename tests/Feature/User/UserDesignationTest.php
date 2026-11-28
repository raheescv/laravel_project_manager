<?php

use App\Livewire\User\Page;
use App\Models\Designation;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The Add / Edit User modal carries an optional designation.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    foreach (['user.create', 'user.edit'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    $this->designation = Designation::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Accountant']);
});

it('creates a user without a designation', function (): void {
    Livewire::test(Page::class)
        ->set('users.name', 'No Title')
        ->set('users.email', 'no-title@example.com')
        ->set('users.mobile', '+97455000001')
        ->set('users.password', 'secret123')
        ->set('users.designation_id', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotDispatched('error');

    expect(User::where('email', 'no-title@example.com')->value('designation_id'))->toBeNull();
});

it('creates a user with a designation', function (): void {
    Livewire::test(Page::class)
        ->set('users.name', 'Has Title')
        ->set('users.email', 'has-title@example.com')
        ->set('users.mobile', '+97455000002')
        ->set('users.password', 'secret123')
        ->set('users.designation_id', (string) $this->designation->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('email', 'has-title@example.com')->value('designation_id'))->toBe($this->designation->id);
});

it('pre-selects the saved designation when editing and can clear it', function (): void {
    $user = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'type' => 'user', 'mobile' => '+97455000003', 'designation_id' => $this->designation->id]);

    Livewire::test(Page::class, ['table_id' => $user->id])
        ->assertSet('users.designation_id', $this->designation->id)
        ->assertDispatched('UserDesignationSelected', designation: ['id' => $this->designation->id, 'name' => 'Accountant'])
        ->set('users.designation_id', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->designation_id)->toBeNull();
});

it('rejects a designation that belongs to another tenant', function (): void {
    $other = Tenant::factory()->create();
    $foreign = Designation::create(['tenant_id' => $other->id, 'name' => 'Foreign']);
    $user = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'type' => 'user', 'mobile' => '+97455000004']);

    Livewire::test(Page::class, ['table_id' => $user->id])
        ->set('users.designation_id', $foreign->id)
        ->call('save')
        ->assertHasErrors(['users.designation_id' => 'exists']);

    expect($user->fresh()->designation_id)->toBeNull();
});

it('renders the designation picker in the user modal', function (): void {
    Livewire::test(Page::class)
        ->assertSeeHtml('id="user_designation_id"')
        ->assertSee('Designation');
});
