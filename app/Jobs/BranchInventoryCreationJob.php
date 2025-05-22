<?php

namespace App\Jobs;

use App\Actions\Product\Inventory\CreateAction;
use App\Models\Inventory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BranchInventoryCreationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public $product, public $branchId, public $userId) {}

    public function handle(): void
    {
        if ($this->product->type == 'service' || true) {

            $exists = Inventory::query()
                ->where('product_id', $this->product->id)
                ->where('branch_id', $this->branchId)
                ->exists();

            if (! $exists) {
                $data['product_id'] = $this->product->id;
                $data['cost'] = $this->product->cost;
                $data['branch_id'] = $this->branchId;
                $data['quantity'] = 0;
                $data['remarks'] = null;
                $data['barcode'] = $this->product->barcode;
                if (! isset($data['barcode'])) {
                    $data['barcode'] = generateBarcode();
                }
                $data['batch'] = $this->product->barcode;
                $data['created_by'] = $data['updated_by'] = $this->userId;
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
        }
    }
}
