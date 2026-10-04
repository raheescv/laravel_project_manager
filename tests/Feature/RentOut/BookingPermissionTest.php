<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Rental and sale bookings each have their own permission group, so a role
 * granted only one of them must reach that module's booking screens alone.
 */
beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    session(['branch_id' => 1]);
});

function grantBooking(User $user, string $group): void
{
    foreach (['view', 'create', 'edit'] as $action) {
        $user->givePermissionTo(Permission::findOrCreate("{$group}.{$action}", 'web'));
    }
}

it('opens sale booking screens with only the lease booking permissions', function (): void {
    grantBooking($this->user, 'rent out lease booking');

    $this->get(route('property::sale::booking'))->assertOk();
    $this->get(route('property::sale::booking.create'))->assertOk();
    $this->get(route('property::rent::booking'))->assertForbidden();
});

it('opens rental booking screens with only the rent out booking permissions', function (): void {
    grantBooking($this->user, 'rent out booking');

    $this->get(route('property::rent::booking'))->assertOk();
    $this->get(route('property::rent::booking.create'))->assertOk();
    $this->get(route('property::sale::booking'))->assertForbidden();
});

it('shows only the granted booking link in the sidebar', function (): void {
    grantBooking($this->user, 'rent out lease booking');

    $this->get(route('property::sale::booking'))
        ->assertSee(route('property::sale::booking'), false)
        ->assertDontSee(route('property::rent::booking'), false);
});
