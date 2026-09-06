<?php

use App\Livewire\Purchase\VendorPayment;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function permUser(array $perms): User
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'perm-test-'.uniqid(), 'guard_name' => 'web']);
    foreach ($perms as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    $role->syncPermissions($perms);
    $user->assignRole($role);

    return $user->fresh();
}

it('allows saving with purchase.payments alone', function () {
    $this->actingAs(permUser(['purchase.payments']));
    Livewire::test(VendorPayment::class)->call('save');
})->throwsNoExceptions();

it('allows saving with local purchase order.payments alone', function () {
    $this->actingAs(permUser(['local purchase order.payments']));
    Livewire::test(VendorPayment::class)->call('save');
})->throwsNoExceptions();

it('blocks saving with neither permission', function () {
    $this->actingAs(permUser(['purchase.view']));
    Livewire::test(VendorPayment::class)->call('save');
})->throws(Symfony\Component\HttpKernel\Exception\HttpException::class);
