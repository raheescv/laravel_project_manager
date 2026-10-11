<?php

namespace App\Support\Audit;

class AuditChanges
{
    /**
     * Drop attributes whose old and new values are the same number written
     * differently (e.g. "0.00" → 0, "26.000" → 26), so they never read as a change.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function withoutEquivalentValues(array $old, array $new): array
    {
        foreach (array_intersect_key($old, $new) as $attribute => $oldValue) {
            if (self::isEquivalent($oldValue, $new[$attribute])) {
                unset($old[$attribute], $new[$attribute]);
            }
        }

        return [$old, $new];
    }

    public static function isEquivalent(mixed $old, mixed $new): bool
    {
        if (! is_numeric($old) || ! is_numeric($new)) {
            return false;
        }

        return (float) $old === (float) $new;
    }
}
