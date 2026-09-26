<?php

use Tests\Support\PosWorld;

it('brands the page with the tenant name, not the shared APP_NAME', function (): void {
    config(['app.name' => 'Solan']);
    $world = PosWorld::create();
    $world->tenant->update(['name' => 'Orga Trading']);
    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    $this->get($world->url(route('dashboard', absolute: false)))
        ->assertOk()
        ->assertSee('<title>Orga Trading</title>', false)
        ->assertDontSee('<title>Solan</title>', false);
});
