<?php

namespace App\Http\Resources\V1\Catalog;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the Settings → Catalog table. Keys match the column keys the app
 * renders (and the `sort` values ListRequest accepts), so a cell is a lookup.
 *
 * @mixin Product
 */
class CatalogItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'name_arabic' => $this->name_arabic,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'department' => $this->department?->name,
            'main_category' => $this->mainCategory?->name,
            'sub_category' => $this->subCategory?->name,
            'unit' => $this->unit?->name,
            'brand' => $this->brand?->name,
            'size' => $this->size,
            'cost' => (float) $this->cost,
            'mrp' => (float) $this->mrp,
            'priority' => (int) $this->priority,
            'time' => (float) $this->time,
            'status' => $this->status,
            'is_selling' => (bool) $this->is_selling,
            'is_favorite' => (bool) $this->is_favorite,
            'thumbnail' => $this->thumbnail,
        ];
    }
}
