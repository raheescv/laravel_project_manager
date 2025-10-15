<?php

namespace App\Actions\V1\Color;

use App\Http\Requests\V1\GetColorsRequest;
use App\Models\Product;

class GetColorsAction
{
    /**
     * Execute the action to get all unique colors with HSN code and product code filtering.
     */
    public function execute(GetColorsRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $query = Product::selectRaw('color, COUNT(*) as product_count')
            ->when($filters['code'] ?? null, function ($q, $value) {
                return $q->where('code', 'like', "%{$value}%");
            })
            ->whereNotNull('color')
            ->where('color', '!=', '')
            ->groupBy('color')
            ->orderBy('color');

        $colors = $query->get();

        return $colors->map(function ($color) {
            return [
                'color' => $color->color,
                'product_count' => $color->product_count,
            ];
        })->toArray();
    }
}
