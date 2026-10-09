<?php

use App\Livewire\User\Employee\Page;
use App\Models\Designation;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The employee form asks "Login access" first; the Authentication and Role
 * Assignment panels only show once it is switched on.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'employee.create', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    $this->designation = Designation::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Staff '.uniqid()]);
});

function loginAccessEmployee(PosWorld $world, array $attributes = []): User
{
    return User::factory()->create([
        'tenant_id' => $world->tenant->id,
        'type' => 'employee',
        'is_admin' => false,
        'last_login_at' => null,
        ...$attributes,
    ]);
}

it('starts with login access off for a new employee and wraps the credential panels', function (): void {
    Livewire::test(Page::class)
        ->assertSet('allowLogin', false)
        ->assertSee('Login access')
        ->assertSeeHtml('x-show="$wire.allowLogin"');
});

it('focuses the password field when login access is switched on', function (): void {
    Livewire::test(Page::class)
        ->assertSeeHtml('$refs.loginPassword?.focus()')
        ->assertSeeHtml('x-ref="loginPassword"');
});

it('starts with login access off when editing an employee without credentials', function (): void {
    $employee = loginAccessEmployee($this->world, ['pin' => null]);

    Livewire::test(Page::class, ['table_id' => $employee->id])->assertSet('allowLogin', false);
});

it('starts with login access on when editing an employee who can already sign in', function (array $attributes): void {
    $employee = loginAccessEmployee($this->world, $attributes);

    Livewire::test(Page::class, ['table_id' => $employee->id])->assertSet('allowLogin', true);
})->with([
    'has a PIN' => [['pin' => '1234']],
    'is an administrator' => [['is_admin' => true]],
    'has signed in before' => [['last_login_at' => now()]],
]);

it('requires a designation', function (): void {
    Livewire::test(Page::class)
        ->set('users.name', 'No Designation')
        ->set('users.designation_id', '')
        ->call('save')
        ->assertHasErrors(['users.designation_id' => 'required']);
});

it('saves an employee without an email while login access is off', function (): void {
    Livewire::test(Page::class)
        ->set('users.name', 'Kitchen Helper')
        ->set('users.email', '')
        ->set('users.designation_id', $this->designation->id)
        ->set('allowLogin', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('name', 'Kitchen Helper')->value('email'))->toBeNull();
});

it('requires an email once login access is on', function (): void {
    Livewire::test(Page::class)
        ->set('users.name', 'Cashier')
        ->set('users.email', '')
        ->set('users.designation_id', $this->designation->id)
        ->set('allowLogin', true)
        ->call('save')
        ->assertHasErrors(['users.email' => 'required']);
});
