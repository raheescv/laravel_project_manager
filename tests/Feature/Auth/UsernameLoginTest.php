<?php

use App\Livewire\User\Page;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Staff sign in with either their email or their username — on the web login
 * screen and through the mobile app's username/password method.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill([
        'username' => 'Front.Desk',
        'password' => bcrypt('secret-pass'),
        'is_active' => true,
    ])->save();
});

it('stores the username trimmed and lower-cased, and blank as null', function (): void {
    expect($this->world->user->fresh()->username)->toBe('front.desk');

    $this->world->user->update(['username' => '   ']);

    expect($this->world->user->fresh()->username)->toBeNull();
});

it('signs in on the web with a username', function (): void {
    $this->post($this->world->url('/login'), ['login' => 'FRONT.DESK', 'password' => 'secret-pass'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($this->world->user);
});

it('signs in on the web with an email', function (): void {
    $this->post($this->world->url('/login'), ['login' => $this->world->user->email, 'password' => 'secret-pass'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($this->world->user);
});

it('still accepts the legacy email field', function (): void {
    $this->post($this->world->url('/login'), ['email' => $this->world->user->email, 'password' => 'secret-pass'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($this->world->user);
});

it('rejects a username with the wrong password', function (): void {
    $this->post($this->world->url('/login'), ['login' => 'front.desk', 'password' => 'wrong'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('signs in on the mobile app with a username', function (): void {
    $this->postJson($this->world->url('/api/v1/login'), [
        'method' => 'password',
        'username' => 'Front.Desk',
        'password' => 'secret-pass',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.username', 'front.desk');
});

it('saves a username from the user form and keeps it unique per tenant', function (): void {
    Permission::firstOrCreate(['name' => 'user.edit'], ['guard_name' => 'web']);
    $admin = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_active' => 1, 'is_admin' => true, 'mobile' => '+97450000000']);
    $admin->givePermissionTo('user.edit');
    $this->actingAs($admin);

    Livewire::test(Page::class, ['table_id' => $admin->id])
        ->set('users.username', 'front.desk')
        ->call('save')
        ->assertHasErrors(['users.username' => 'unique']);

    Livewire::test(Page::class, ['table_id' => $admin->id])
        ->set('users.username', 'bad name!')
        ->call('save')
        ->assertHasErrors(['users.username' => 'regex']);

    Livewire::test(Page::class, ['table_id' => $admin->id])
        ->set('users.username', 'Manager_1')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->fresh()->username)->toBe('manager_1');
});
