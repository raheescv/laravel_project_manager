<?php

use App\Actions\Product\CreateAction;
use App\Models\Inventory;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PosWorld;

/**
 * A service is sold through its branch inventory row like any product, so
 * creating one must open a zero-stock inventory row in every branch.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    TenantCache::forget('branches');
});

it('creates a zero-stock inventory row in the branch for a new service', function (): void {
    $response = (new CreateAction())->execute([
        'type' => 'service',
        'name' => 'Hair Cut',
        'unit_id' => $this->world->product->unit_id,
        'department_id' => $this->world->product->department_id,
        'main_category_id' => $this->world->product->main_category_id,
        'mrp' => 30,
        'opening_stock' => 5,
    ], $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message']);

    $inventory = Inventory::query()
        ->where('product_id', $response['data']->id)
        ->where('branch_id', $this->world->branch->id)
        ->first();

    expect($inventory)->not->toBeNull()
        ->and((float) $inventory->quantity)->toBe(0.0);
});
