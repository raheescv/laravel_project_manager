<?php

use App\Jobs\BranchInventoryCreationJob;
use App\Jobs\BranchProductCreationJob;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Tenant;
use App\Services\TenantService;
use Tests\Support\PosWorld;

/**
 * A new branch gets an empty inventory row for every product, built by queued jobs.
 * The queue worker has no request, so the jobs must carry the tenant themselves —
 * otherwise tenant_id is left off the insert and the inventories FK rejects it.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->newBranch = Branch::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Second Branch', 'code' => 'SB']);
});

it('opens inventory in the new branch when the worker has no tenant', function (): void {
    $job = new BranchProductCreationJob($this->newBranch->id, $this->world->user->id);

    app(TenantService::class)->clearCurrentTenant();
    config(['constants.tenant_id' => null]);

    $job->handle();

    $inventory = Inventory::withoutTenant()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->newBranch->id)
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->tenant_id)->toBe($this->world->tenant->id)
        ->and((float) $inventory->quantity)->toBe(0.0);
});

it('never pairs a product with another tenant\'s branch', function (): void {
    $otherTenant = Tenant::factory()->create();
    $foreignBranch = Branch::withoutEvents(fn () => Branch::create(['tenant_id' => $otherTenant->id, 'name' => 'Foreign', 'code' => 'FB']));

    $job = new BranchProductCreationJob(null, $this->world->user->id, $this->world->product->id);
    app(TenantService::class)->clearCurrentTenant();
    config(['constants.tenant_id' => null]);

    $job->handle();

    expect(Inventory::withoutTenant()->where('branch_id', $foreignBranch->id)->exists())->toBeFalse()
        ->and(Inventory::withoutTenant()->where('branch_id', $this->newBranch->id)->exists())->toBeTrue();
});

it('leaves the caller\'s tenant in place after running inline', function (): void {
    (new BranchInventoryCreationJob($this->world->product->fresh(), $this->newBranch->id, $this->world->user->id))->handle();

    expect(app(TenantService::class)->getCurrentTenantId())->toBe($this->world->tenant->id);
});
