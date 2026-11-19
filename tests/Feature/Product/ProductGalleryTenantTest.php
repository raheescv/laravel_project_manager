<?php

use App\Actions\Product\DeleteImageAction;
use App\Livewire\Product\Gallery;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * product_images has no tenant_id of its own, so the gallery must only ever
 * reach images through a product owned by the current tenant.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    foreach (['product.view', 'product.delete'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->ownImage = ProductImage::create([
        'product_id' => $this->world->product->id,
        'method' => 'normal',
        'path' => '/storage/products/own-image.jpg',
        'name' => 'own-image.jpg',
    ]);

    $otherProduct = $this->world->product->replicate();
    $otherProduct->tenant_id = Tenant::factory()->create()->id;
    $otherProduct->name = 'Other Tenant Product';
    $otherProduct->saveQuietly();

    $this->foreignImage = ProductImage::create([
        'product_id' => $otherProduct->id,
        'method' => 'normal',
        'path' => '/storage/products/foreign-image.jpg',
        'name' => 'foreign-image.jpg',
    ]);
});

it('lists only images of the current tenant products', function (): void {
    $component = Livewire::test(Gallery::class);

    $ids = $component->viewData('data')->pluck('id')->all();

    expect($ids)->toContain($this->ownImage->id)
        ->not->toContain($this->foreignImage->id);
});

it('select all never picks images from another tenant', function (): void {
    Livewire::test(Gallery::class)
        ->set('selectAll', true)
        ->assertSet('selected', [$this->ownImage->id]);
});

it('refuses to delete an image belonging to another tenant', function (): void {
    Livewire::test(Gallery::class)
        ->set('selected', [$this->foreignImage->id])
        ->call('deleteSelected')
        ->assertDispatched('error');

    expect(ProductImage::find($this->foreignImage->id))->not->toBeNull();

    $response = (new DeleteImageAction())->execute($this->foreignImage->id);

    expect($response['success'])->toBeFalse()
        ->and(ProductImage::find($this->foreignImage->id))->not->toBeNull()
        ->and(Product::withoutTenant()->count())->toBe(2);
});
