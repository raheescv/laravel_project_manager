<?php

use App\Livewire\Product\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * A save that fails after validation must leave the gallery's saved images in
 * place; the pending uploads went to the action, not into `products.images`,
 * which the gallery reads as rows with `path`.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'product.edit', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('keeps pending uploads out of the gallery when the save fails', function (): void {
    $component = Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->set('images', [UploadedFile::fake()->image('front.jpg')]);

    $this->world->product->delete();

    $component->call('save')
        ->assertOk()
        ->assertDispatched('error')
        ->assertSet('products.images', []);
});
