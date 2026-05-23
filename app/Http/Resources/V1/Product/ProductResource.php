<?php

namespace App\Http\Resources\V1\Product;

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
            'id' => (string) $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'price' => (float) $this->mrp,
            'thumbnail' => $this->thumbnail,
            'unit' => $this->unit?->name,
            'category' => $this->mainCategory?->name,
            'brand' => $this->brand?->name,
            'stock' => $this->when(
                ! is_null($this->inventories_sum_quantity),
                fn () => (float) $this->inventories_sum_quantity,
            ),
        ];
    }
}
