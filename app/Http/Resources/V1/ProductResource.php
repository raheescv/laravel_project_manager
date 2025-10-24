<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;

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
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => $image->path,
                    ];
                });
            }),

            'inventories' => $this->whenLoaded('inventories', function () {
                return $this->inventories->map(function ($inventory) {
                    return [
                        'id' => $inventory->id,
                        'branch' => [
                            'id' => $inventory->branch->id,
                            'name' => $inventory->branch->name,
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
            'available_sizes' => $this->getAvailableSizes(),
        ];
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
}
