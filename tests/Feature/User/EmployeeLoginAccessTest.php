<?php

use App\Actions\V1\Auth\LoginAction;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Livewire\User\Employee\Page;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
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

it('rejects a PIN that is not exactly 4 digits', function (string $pin): void {
    Livewire::test(Page::class)
        ->set('users.name', 'Cashier')
        ->set('users.designation_id', $this->designation->id)
        ->set('users.pin', $pin)
        ->call('save')
        ->assertHasErrors(['users.pin' => 'digits']);
})->with(['too short' => '123', 'too long' => '12345', 'not numeric' => '12a4']);

it('rejects a PIN already used by another user on the tenant', function (): void {
    loginAccessEmployee($this->world, ['pin' => '4821']);

    Livewire::test(Page::class)
        ->set('users.name', 'Cashier')
        ->set('users.designation_id', $this->designation->id)
        ->set('users.pin', '4821')
        ->call('save')
        ->assertHasErrors(['users.pin']);
});

it('shows the current PIN when editing, including after a save', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'employee.edit', 'guard_name' => 'web']));
    $employee = loginAccessEmployee($this->world, ['pin' => '7395', 'designation_id' => $this->designation->id]);

    Livewire::test(Page::class, ['table_id' => $employee->id])
        ->assertSet('users.pin', '7395')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('users.pin', '7395');

    expect($employee->fresh()->revealPin())->toBe('7395');
});

it('keeps the current PIN when the field is cleared, and its own PIN is not a duplicate', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'employee.edit', 'guard_name' => 'web']));
    $employee = loginAccessEmployee($this->world, ['pin' => '7395', 'designation_id' => $this->designation->id]);

    Livewire::test(Page::class, ['table_id' => $employee->id])
        ->set('users.pin', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->revealPin())->toBe('7395');
});

it('flags a legacy PIN that cannot be shown', function (): void {
    $employee = loginAccessEmployee($this->world, ['pin' => Hash::make('1111')]);

    Livewire::test(Page::class, ['table_id' => $employee->id])
        ->assertSet('users.pin', '')
        ->assertSet('hasHiddenPin', true);
});

function pinLoginRequest(string $pin): LoginRequest
{
    $request = LoginRequest::create('/', 'POST', ['pin' => $pin]);
    $request->setContainer(app())->setRedirector(app('redirect'))->validateResolved();

    return $request;
}

it('stores the PIN encrypted in the pin column', function (): void {
    $employee = loginAccessEmployee($this->world, ['pin' => '7395']);
    $storedPin = $employee->fresh()->getAttributes()['pin'];

    expect($storedPin)->not->toBe('7395')
        ->and(User::isHashedPin($storedPin))->toBeFalse()
        ->and(Crypt::decryptString($storedPin))->toBe('7395');
});

it('signs in with an encrypted PIN', function (): void {
    $employee = loginAccessEmployee($this->world, ['pin' => '5821', 'is_active' => true]);

    $response = (new LoginAction())->execute(pinLoginRequest('5821'));

    expect($response['user']->resource->id)->toBe($employee->id);
});

it('rejects a wrong PIN', function (): void {
    loginAccessEmployee($this->world, ['pin' => '5821', 'is_active' => true]);

    (new LoginAction())->execute(pinLoginRequest('5822'));
})->throws(AuthenticationException::class);

it('converts a legacy hashed PIN to an encrypted one the next time it is used to sign in', function (): void {
    $employee = loginAccessEmployee($this->world, ['pin' => Hash::make('2468'), 'is_active' => true]);

    (new LoginAction())->execute(pinLoginRequest('2468'));

    expect($employee->fresh()->revealPin())->toBe('2468')
        ->and(User::isHashedPin($employee->fresh()->getAttributes()['pin']))->toBeFalse();
});
