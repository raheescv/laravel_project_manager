<?php

use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\UserHasBranch;
use App\Services\TenantService;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PosWorld;

/**
 * Child tables without a tenant_id (product_images, product_prices,
 * user_has_branches, sale_payments, …) are scoped through their parent, so
 * every query — listings, aggregates and find() — only sees the current tenant.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->other = PosWorld::create();
    $this->otherImage = ProductImage::create(['product_id' => $this->other->product->id, 'method' => 'normal', 'path' => '/storage/b.jpg', 'name' => 'b.jpg']);
    $this->otherPrice = ProductPrice::create(['product_id' => $this->other->product->id, 'price_type' => 'normal', 'amount' => 99, 'status' => 'active']);
    $this->otherBranchLink = UserHasBranch::create(['user_id' => $this->other->user->id, 'branch_id' => $this->other->branch->id]);

    $this->world = PosWorld::create();
    $this->ownImage = ProductImage::create(['product_id' => $this->world->product->id, 'method' => 'normal', 'path' => '/storage/a.jpg', 'name' => 'a.jpg']);
    $this->ownPrice = ProductPrice::create(['product_id' => $this->world->product->id, 'price_type' => 'normal', 'amount' => 10, 'status' => 'active']);
    $this->ownBranchLink = UserHasBranch::create(['user_id' => $this->world->user->id, 'branch_id' => $this->world->branch->id]);
});

it('hides other tenants rows from listings, aggregates and find', function (): void {
    expect(ProductImage::pluck('id')->all())->toBe([$this->ownImage->id])
        ->and(ProductImage::find($this->otherImage->id))->toBeNull()
        ->and((float) ProductPrice::sum('amount'))->toBe(10.0)
        ->and(ProductPrice::find($this->otherPrice->id))->toBeNull()
        ->and(UserHasBranch::pluck('id')->all())->toBe([$this->ownBranchLink->id])
        ->and(UserHasBranch::find($this->otherBranchLink->id))->toBeNull();
});

it('still loads own children through the parent relation', function (): void {
    expect($this->world->product->images()->pluck('id')->all())->toBe([$this->ownImage->id]);
});

it('sees every tenant with withoutTenant or when no tenant is resolved', function (): void {
    expect(ProductImage::withoutTenant()->count())->toBe(2);

    app(TenantService::class)->clearCurrentTenant();

    expect(ProductImage::count())->toBe(2);
});
