<?php

namespace App\Jobs;

use App\Actions\Product\Inventory\CreateAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Opens an empty inventory row for a product in a branch.
 *
 * The product's tenant is pinned for the run: without it the worker resolves no tenant,
 * tenant_id is left off the insert and the inventories → tenants foreign key rejects it.
 */
class BranchInventoryCreationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public $product, public $branchId, public $userId) {}

    public function handle(): void
    {
        $tenant = Tenant::find($this->product->tenant_id);
        if (! $tenant) {
            return;
        }

        $tenantService = app(TenantService::class);
        $previousTenant = $tenantService->getCurrentTenant();
        $tenantService->setCurrentTenant($tenant);

        try {
            $this->createInventory();
        } finally {
            $previousTenant ? $tenantService->setCurrentTenant($previousTenant) : $tenantService->clearCurrentTenant();
        }
    }

    protected function createInventory(): void
    {
        if (! Branch::query()->whereKey($this->branchId)->exists()) {
            return;
        }

        $exists = Inventory::query()
            ->where('product_id', $this->product->id)
            ->where('branch_id', $this->branchId)
            ->exists();

        if ($exists) {
            return;
        }

        $data['tenant_id'] = $this->product->tenant_id;
        $data['product_id'] = $this->product->id;
        $data['cost'] = $this->product->cost;
        $data['branch_id'] = $this->branchId;
        $data['quantity'] = 0;
        $data['remarks'] = null;
        $data['barcode_number'] = $this->product->barcode_number;
        if (! isset($data['barcode_number'])) {
            $data['barcode_number'] = generateBarcode();
        }
        $data['batch'] = $data['barcode_number'];
        $data['created_by'] = $data['updated_by'] = $this->userId;
        $response = (new CreateAction())->execute($data);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
