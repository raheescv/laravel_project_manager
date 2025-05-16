<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'name_arabic' => $this->name_arabic,
            'thumbnail' => $this->thumbnail,
            'unit' => $this->unit?->name,
            'main_category' => $this->mainCategory?->name,
            'hsn_code' => $this->hsn_code,
            'tax' => $this->tax,
            'description' => $this->description,
            'is_selling' => (bool) $this->is_selling,
            'is_favorite' => (bool) $this->is_favorite,
            'mrp' => (float) $this->mrp,
            'barcode' => $this->barcode,
        ];
    }
}
