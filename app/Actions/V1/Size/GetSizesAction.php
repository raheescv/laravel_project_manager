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

        $query = Product::selectRaw('size')
            ->when($filters['code'] ?? null, function ($q, $value) {
                return $q->where('code', 'like', "%{$value}%");
            })
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->groupBy('size')
            ->orderBy('size');

        $sizes = $query->get()->pluck('size')->filter()->values();

        $kidsKeywords = ['KID', 'KIDS', 'BOY', 'BOYS', 'GIRL', 'GIRLS', 'BABY', 'INFANT', 'TODDLER', 'YR', 'YRS', 'YEAR', 'YEARS'];

        $isKidsSize = function (string $size) use ($kidsKeywords): bool {
            $upper = strtoupper(trim($size));

            foreach ($kidsKeywords as $kw) {
                if (str_contains($upper, $kw)) {
                    return true;
                }
            }

            // Numeric-only sizes up to 34 considered kids by default (tweak if needed)
            if (preg_match('/^\d{1,2}$/', $upper)) {
                return (int) $upper <= 34;
            }

            // Common kids patterns like 0-3M, 6-12M, 2-3Y, etc.
            if (preg_match('/^(\d+\s*-\s*\d+\s*)(M|MOS|MONTH|MONTHS|Y|YR|YRS|YEAR|YEARS)$/i', $upper)) {
                return true;
            }
            if (preg_match('/^(\d+)(M|MOS|MONTH|MONTHS|Y|YR|YRS|YEAR|YEARS)$/i', $upper)) {
                return true;
            }

            return false;
        };

        $kids = [];
        $others = [];

        foreach ($sizes as $size) {
            $item = ['size' => $size];
            if ($isKidsSize((string) $size)) {
                $kids[] = $item;
            } else {
                $others[] = $item;
            }
        }

        return [
            'kids_sizes' => $kids,
            'other_sizes' => $others,
        ];
    }
}
