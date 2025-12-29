<?php

namespace App\Http\Resources\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_arabic' => $this->name_arabic,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'barcode' => $this->barcode,
            'color' => $this->color,
            'size' => $this->size,
            'model' => $this->model,
            'hsn_code' => $this->hsn_code,
            // 'cost' => $this->cost,
            'mrp' => $this->mrp,
            'location' => $this->location,
            // 'reorder_level' => $this->reorder_level,
            // 'plu' => $this->plu,
            'priority' => $this->priority,
            // 'status' => $this->status,
            // 'is_selling' => $this->is_selling,
            // 'is_favorite' => $this->is_favorite,
            'time' => $this->time,
            'created_at' => systemDateTime($this->created_at),
            'updated_at' => systemDateTime($this->updated_at),

            // Relationships
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'code' => $this->unit->code,
                ];
            }),

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),

            // 'department' => $this->whenLoaded('department', function () {
            //     return [
            //         'id' => $this->department->id,
            //         'name' => $this->department->name,
            //     ];
            // }),

            'main_category' => $this->whenLoaded('mainCategory', function () {
                return [
                    'id' => $this->mainCategory->id,
                    'name' => $this->mainCategory->name,
                ];
            }),

            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                ];
            }),

            'images' => $this->whenLoaded('images', function () {
                return $this->normalImages()->get()->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url,
                        'name' => $image->name,
                        'size' => $image->size,
                        'type' => $image->type,
                        'method' => $image->method,
                    ];
                });
            }),

            'images360' => $this->whenLoaded('images', function () {
                return $this->angleImages()->orderedByAngle()->get()->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url,
                        'name' => $image->name,
                        'size' => $image->size,
                        'type' => $image->type,
                        'method' => $image->method,
                        'degree' => $image->degree,
                        'sort_order' => $image->sort_order,
                    ];
                });
            }),

            'inventories' => $this->whenLoaded('inventories', function () {
                return $this->inventories->map(function ($inventory) {
                    return [
                        'id' => $inventory->id,
                        'branch' => [
                            'id' => $inventory->branch?->id,
                            'name' => $inventory->branch?->name,
                        ],
                        'quantity' => $inventory->quantity,
                        'is_low_stock' => $inventory->quantity <= $this->reorder_level,
                        'is_out_of_stock' => $inventory->quantity == 0,
                    ];
                });
            }),

            // Computed fields
            'total_stock' => $this->when($this->relationLoaded('inventories'), function () {
                return $this->inventories->sum('quantity');
            }),

            'is_low_stock' => $this->when($this->relationLoaded('inventories'), function () {
                $totalStock = $this->inventories->sum('quantity');

                return $totalStock <= $this->min_stock;
            }),

            'is_out_of_stock' => $this->when($this->relationLoaded('inventories'), function () {
                $availableStock = $this->inventories->sum('quantity');

                return $availableStock <= 0;
            }),

            'stock_quantity_availability_status' => $this->when($this->relationLoaded('inventories'), function () {
                return $this->getStockQuantityAvailabilityStatus();
            }),

            'available_sizes' => $this->getAvailableSizes(),
            'related_sizes' => $this->getRelatedSizes(),
        ];
    }

    /**
     * Get stock quantity availability status based on selected branch.
     */
    private function getStockQuantityAvailabilityStatus(): string
    {
        if (! $this->relationLoaded('inventories') || $this->inventories->isEmpty()) {
            return 'out_of_stock';
        }

        $selectedBranchId = session('branch_id');
        $selectedBranchStock = 0;
        $otherBranchesStock = 0;

        foreach ($this->inventories as $inventory) {
            if ($inventory->branch_id == $selectedBranchId) {
                $selectedBranchStock += $inventory->quantity;
            } else {
                $otherBranchesStock += $inventory->quantity;
            }
        }

        // If available in selected branch
        if ($selectedBranchStock > 0) {
            return 'in_stock';
        }

        // If available in other branches but not in selected branch
        if ($otherBranchesStock > 0) {
            return 'available_in_other_branches';
        }

        // Not available anywhere
        return 'out_of_stock';
    }

    /**
     * Get available sizes for products with the same base code.
     */
    private function getAvailableSizes(): array
    {
        $list = Product::query()
            ->where('code', $this->code)
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->pluck('size')->toArray();

        return $list;
    }

    /**
     * Get related product sizes from products with names containing the base name.
     */
    private function getRelatedSizes(): array
    {
        // Extract base name by removing trailing numbers and spaces
        $baseName = preg_replace('/\s+\d+$/', '', trim($this->name));

        // If base name is too short, use the full name
        if (strlen($baseName) < 3) {
            $baseName = $this->name;
        }

        // Get related products with their inventories and branches
        $relatedProducts = Product::query()
            ->where('name', 'like', $baseName.'%')
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->with('inventories.branch:id,name')
            ->get();

        // Group by size and calculate stock by branch for each size
        $sizesWithStock = $relatedProducts->groupBy('size')->map(function ($products, $size) {
            // Collect all inventories for products with this size
            $allInventories = $products->flatMap(function ($product) {
                return $product->inventories;
            });

            // Group inventories by branch and sum quantities
            $branchStock = $allInventories->groupBy('branch_id')->map(function ($inventories, $branchId) {
                $firstInventory = $inventories->first();
                $branch = $firstInventory->branch;

                if (! $branch) {
                    return;
                }

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'quantity' => $inventories->sum('quantity'),
                ];
            })->filter()->values()->toArray();

            $totalStock = $allInventories->sum('quantity');

            return [
                'size' => $size,
                'total_stock' => $totalStock,
                'is_out_of_stock' => $totalStock <= 0,
                'branches' => $branchStock,
            ];
        })->values()->toArray();

        return $sizesWithStock;
    }
}
