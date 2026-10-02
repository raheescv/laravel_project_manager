<?php

use App\Livewire\User\Page;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

test('an admin can change their own administrator access', function (): void {
    Permission::firstOrCreate(['name' => 'user.edit'], ['guard_name' => 'web']);

    $admin = User::factory()->create(['tenant_id' => 1, 'is_active' => 1, 'is_admin' => true, 'mobile' => '+97450000000']);
    $admin->givePermissionTo('user.edit');

    $this->actingAs($admin);

    Livewire::test(Page::class, ['table_id' => $admin->id])
        ->assertDontSee('You cannot change your own administrator access.')
        ->set('isAdmin', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotDispatched('error');

    expect((bool) $admin->fresh()->is_admin)->toBeFalse();
});
