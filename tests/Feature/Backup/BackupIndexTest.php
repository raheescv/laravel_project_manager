<?php

use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

it('shows how long ago each backup was taken', function (): void {
    $world = PosWorld::create();
    $world->user->givePermissionTo(Permission::firstOrCreate([
        'name' => 'backup.view', 'guard_name' => 'web',
    ]));
    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    Storage::fake();
    $path = config('backup.backup.name').'/backup.zip';
    Storage::put($path, 'dump');
    touch(Storage::path($path), now()->subHours(3)->timestamp);

    $this->get($world->url(route('backup::index', absolute: false)))
        ->assertOk()
        ->assertSee('Age')
        ->assertSee('3 hours ago');
});
