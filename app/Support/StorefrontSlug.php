<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The showcase website puts names in its URLs, never ids: #/product/nike-air-max-90,
 * #/?brand=new-balance. A name that cannot make a slug (Arabic, digits only) goes in
 * as itself, so every link is read back by matching the name first, then the slug.
 */
class StorefrontSlug
{
    /** "Nike Air Max 90" → "nike-air-max-90"; null when the name cannot make one. */
    public static function make(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '' || ! preg_match('/^[\p{Latin}\p{N}\p{P}\p{S}\p{Zs}]+$/u', $name)) {
            return null;
        }

        $slug = Str::slug($name, '-', 'en', []);

        return $slug === '' || ctype_digit(str_replace('-', '', $slug)) ? null : $slug;
    }

    /** What a URL carries for [name]: its slug, or the trimmed name itself. */
    public static function link(?string $name): string
    {
        return self::make($name) ?? trim((string) $name);
    }

    /**
     * The row [reference] names: an exact name match, else the row whose slug it is.
     * The query's own ordering decides between rows that share a name.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return TModel|null
     */
    public static function resolve(Builder $query, string $reference, string $column = 'name'): ?Model
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $exact = (clone $query)->where($column, $reference)->first();
        if ($exact || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $reference)) {
            return $exact;
        }

        $attribute = Str::afterLast($column, '.');

        return (clone $query)
            ->where($column, 'like', '%'.str_replace('-', '%', $reference).'%')
            ->limit(200)
            ->get()
            ->first(fn (Model $row): bool => self::make($row->getAttribute($attribute)) === $reference);
    }
}
