<?php

namespace App\Http\Resources\V1\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;

/**
 * The Add / Edit form's view of a product or service: the list row plus the
 * ids behind each picker and the fields only the form shows.
 *
 * @mixin Product
 */
class CatalogItemDetailResource extends CatalogItemResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'department_id' => $this->department_id,
            'main_category_id' => $this->main_category_id,
            'sub_category_id' => $this->sub_category_id,
            'unit_id' => $this->unit_id,
            'brand_id' => $this->brand_id,
            'size_category' => $this->size_category,
            'hsn_code' => $this->hsn_code,
            'tax' => (float) $this->tax,
            'barcode_number' => $this->barcode_number,
            'description' => $this->description,
            'images' => $this->images
                ->where('method', 'normal')
                ->values()
                ->map(fn (ProductImage $image) => ['id' => $image->id, 'path' => $image->path])
                ->all(),
        ];
    }
}
