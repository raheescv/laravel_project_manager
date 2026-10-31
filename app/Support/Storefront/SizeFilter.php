<?php

namespace App\Support\Storefront;

/**
 * The sizes a catalog read is narrowed to.
 *
 * `size` is the original single-value parameter and stays for older callers;
 * `sizes[]` lets the showcase ask for several at once ("42, 43 or 44"). Both
 * are merged, so a caller sending either — or both — gets the union.
 */
class SizeFilter
{
    /**
     * @return list<string>
     */
    public static function from(mixed $size, mixed $sizes): array
    {
        $values = array_merge(
            is_array($size) ? $size : [$size],
            is_array($sizes) ? $sizes : [$sizes],
        );

        return array_values(array_unique(array_filter(
            array_map(fn ($value): string => trim((string) $value), $values),
            fn (string $value): bool => $value !== '',
        )));
    }
}
