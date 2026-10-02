<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\Product;
use App\Services\TenantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fans a product (or every product) out to a branch (or every branch) by queueing
 * one BranchInventoryCreationJob per pair.
 *
 * A queue worker has no request to resolve the tenant from, so the tenant is captured
 * when the job is dispatched and each product is only paired with its own tenant's branches.
 */
class BranchProductCreationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public $branchId, public $userId, public $productId = null, public ?int $tenantId = null)
    {
        $this->tenantId ??= app(TenantService::class)->getCurrentTenantId();
    }

    public function handle(): void
    {
        $products = Product::withoutTenant()
            ->when($this->tenantId, fn ($query, $value) => $query->where('tenant_id', $value))
            ->when($this->productId, fn ($query, $value) => $query->where('id', $value))
            ->get();

        foreach ($products as $product) {
            $branchIds = Branch::withTenant($product->tenant_id)
                ->when($this->branchId, fn ($query, $value) => $query->where('id', $value))
                ->pluck('id');

            foreach ($branchIds as $branchId) {
                BranchInventoryCreationJob::dispatch($product, $branchId, $this->userId);
            }
        }
    }
}
