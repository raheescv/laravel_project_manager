<?php

namespace App\Actions\V1\Size;

use App\Http\Requests\V1\GetSizesRequest;
use App\Models\Product;

class GetSizesAction
{
    /**
     * Execute the action to get all unique sizes with HSN code and product code filtering.
     */
    public function execute(GetSizesRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $query = Product::selectRaw('size, COUNT(*) as product_count')
            ->when($filters['code'] ?? null, function ($q, $value) {
                return $q->where('code', 'like', "%{$value}%");
            })
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->groupBy('size')
            ->orderBy('size');

        $sizes = $query->get();

        return $sizes->map(function ($size) {
            return [
                'size' => $size->size,
                'product_count' => $size->product_count,
            ];
        })->toArray();
    }
}
