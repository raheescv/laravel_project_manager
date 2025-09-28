<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BranchProductCreationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public $branchId, public $userId, public $productId = null) {}

    public function handle(): void
    {
        $products = Product::query()
            ->when($this->productId, function ($query, $value) {
                return $query->where('id', $value);
            })
            ->get();
        foreach ($products as $product) {
            $branches = Branch::query()
                ->when($this->branchId, function ($query, $value) {
                    return $query->where('id', $value);
                })
                ->pluck('id', 'id');
            foreach ($branches as $branchId) {
                BranchInventoryCreationJob::dispatch($product, $branchId, $this->userId);
            }
        }
    }
}
