<?php

use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /users is labelled "Admin Users" in both the sidebar link and the page title.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'user.view', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('labels the users list as Admin Users in the sidebar and page title', function (): void {
    $this->get($this->world->url('/users'))
        ->assertSuccessful()
        ->assertSee('<div class="h-ref">Admin Users</div>', false)
        ->assertSee('Admin Users</a>', false)
        ->assertSee('collapse show">Admin</span>', false)
        ->assertDontSee('Users Directory');
});
