<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BranchProductCreationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public $branchId, public $userId) {}

    public function handle(): void
    {
        $products = Product::get();
        foreach ($products as $product) {
            BranchInventoryCreationJob::dispatch($product, $this->branchId, $this->userId);
        }
    }
}
