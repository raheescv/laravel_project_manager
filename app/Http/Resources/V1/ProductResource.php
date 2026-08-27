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
        // The list endpoint only needs the lightweight card fields. The heavy
        // per-row lookups (available/related sizes, 360° images) are detail-view
        // concerns — computing them for every row triggers an N+1 query storm,
        // so they are emitted only for the single-product endpoints.
        $isList = $request->routeIs('api.v1.products.index');

        // Stock reaches the resource one of two ways: as `stock_total` /
        // `stock_in_branch` aggregates (the list) or as a loaded inventories
        // relation (the detail views). Either is enough to emit the figures.
        $hasStock = $this->hasStockAggregate() || $this->relationLoaded('inventories');

        return [
            'id' => $this->id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'name_arabic' => $this->name_arabic,
            'description' => $this->description,
            'thumbnail' => $this->resolvedThumbnail(),
            'barcode' => $this->barcode,
            'color' => $this->color,
            'size' => $this->size,
            'model' => $this->model,
            'hsn_code' => $this->hsn_code,
            // 'cost' => $this->cost,
            'mrp' => $this->mrp,
            'tax' => $this->tax, // percentage; the app adds this to the line total so its grand total matches the server
            'location' => $this->location,
            // 'reorder_level' => $this->reorder_level,
            // 'plu' => $this->plu,
            'priority' => $this->priority,
            // 'status' => $this->status,
            // 'is_selling' => $this->is_selling,
            // 'is_favorite' => $this->is_favorite,
            'time' => $this->time,
            'document_file' => $this->document_file,
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
                    // Same key and absolute-URL shape as GET /brands, so a client
                    // renders a brand logo the same way wherever it came from.
                    'image_path' => $this->brandLogoUrl(),
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

            // Full image list is a detail-view concern. On the list we eager-load
            // normal images only to resolve the card thumbnail (see below), so skip
            // this per-row re-query there to avoid an N+1.
            'images' => $this->when(! $isList && $this->relationLoaded('images'), function () {
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

            'images360' => $this->when(! $isList && $this->relationLoaded('images'), function () {
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

            // The per-branch breakdown is a detail-view concern. On the list the
            // stock figures come from SQL aggregates instead (see $hasStock),
            // so no inventory row is hydrated for a card that never shows one.
            'inventories' => $this->when(! $isList && $this->relationLoaded('inventories'), function () {
                return $this->inventories->map(function ($inventory) {
                    return [
                        'id' => $inventory->id,
                        'branch' => [
                            'id' => $inventory->branch?->id,
                            'name' => $inventory->branch?->location,
                        ],
                        'quantity' => $inventory->quantity,
                        'is_low_stock' => $inventory->quantity <= $this->reorder_level,
                        'is_out_of_stock' => $inventory->quantity == 0,
                    ];
                });
            }),

            // Computed fields
            'total_stock' => $this->when($hasStock, fn () => $this->totalStock()),

            'is_low_stock' => $this->when($hasStock, fn () => $this->totalStock() <= $this->min_stock),

            'is_out_of_stock' => $this->when($hasStock, fn () => $this->totalStock() <= 0),

            'stock_quantity_availability_status' => $this->when($hasStock, fn () => $this->getStockQuantityAvailabilityStatus()),

            'available_sizes' => $this->when(! $isList, fn () => $this->getAvailableSizes()),
            'related_sizes' => $this->when(! $isList, fn () => $this->getRelatedSizes()),
        ];
    }

    /**
     * The card thumbnail: the explicit thumbnail column when set, otherwise the
     * first normal product image. Keeps mobile/POS product cards from showing a
     * placeholder icon for products that have images but no thumbnail chosen.
     */
    private function resolvedThumbnail(): ?string
    {
        if (! empty($this->thumbnail)) {
            return $this->thumbnail;
        }

        // List: the first normal image's path, selected as a subquery column so
        // the images relation never has to be loaded for a single URL.
        if (! empty($this->fallback_image_path)) {
            return url($this->fallback_image_path);
        }

        if ($this->relationLoaded('images')) {
            $image = $this->images->firstWhere('method', 'normal') ?? $this->images->first();

            return $image?->url;
        }

        return $this->thumbnail;
    }

    /**
     * The brand's logo as an absolute URL. `brands.image_path` holds a path
     * relative to the public disk, exactly as GET /brands resolves it.
     */
    private function brandLogoUrl(): ?string
    {
        $path = $this->brand?->image_path;

        return $path ? url('storage/'.$path) : null;
    }

    /**
     * Whether the query selected the stock aggregates. Checked against the raw
     * attributes rather than the value: a product with no inventory rows sums
     * to NULL, and that still means "stock is known, and it is zero".
     */
    private function hasStockAggregate(): bool
    {
        return array_key_exists('stock_total', $this->resource->getAttributes());
    }

    /**
     * The stock figure the response reports, from whichever source the query
     * used. When the caller filtered by branch this is that branch's stock —
     * matching the branch-scoped inventories the list used to eager-load — and
     * otherwise it is the catalog-wide total.
     */
    private function totalStock(): int|float
    {
        if ($this->hasStockAggregate()) {
            return $this->hasBranchStockAggregate()
                ? $this->numeric($this->stock_in_branch)
                : $this->numeric($this->stock_total);
        }

        return $this->relationLoaded('inventories') ? $this->inventories->sum('quantity') : 0;
    }

    /**
     * Whether the query also summed stock for a specific branch.
     */
    private function hasBranchStockAggregate(): bool
    {
        return array_key_exists('stock_in_branch', $this->resource->getAttributes());
    }

    /**
     * A SUM() comes back as a numeric string, or NULL when the product has no
     * inventory rows at all.
     */
    private function numeric(mixed $value): int|float
    {
        return is_numeric($value) ? $value + 0 : 0;
    }

    /**
     * Get stock quantity availability status based on selected branch.
     */
    private function getStockQuantityAvailabilityStatus(): string
    {
        // List: derived from the aggregates. `stock_in_branch` is only selected
        // when the caller asked for a branch — with no branch in play there is
        // no "other branch" to fall back to, so any stock simply means in stock.
        if ($this->hasStockAggregate()) {
            $everywhere = $this->numeric($this->stock_total);

            if (! $this->hasBranchStockAggregate()) {
                return $everywhere > 0 ? 'in_stock' : 'out_of_stock';
            }

            if ($this->numeric($this->stock_in_branch) > 0) {
                return 'in_stock';
            }

            return $everywhere > 0 ? 'available_in_other_branches' : 'out_of_stock';
        }

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
            ->with('inventories.branch:id,name,location')
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
                    'name' => $branch->location,
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
