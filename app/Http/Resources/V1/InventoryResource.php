<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'product_id' => (string) $this->product_id,
            'name' => $this->product?->name,
            'code' => $this->product?->code,
            'type' => $this->product?->type,
            'mrp' => (float) $this->product?->mrp,
            'thumbnail' => $this->product?->thumbnail,
            'branch_id' => (string) $this->branch_id,
            'branch_name' => $this->branch?->name,
            'barcode' => $this->barcode,
            'quantity' => (float) $this->quantity,
        ];
    }
}
