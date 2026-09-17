<?php

namespace App\Actions\Student\PreOrder;

use App\Models\CanteenMenu;
use App\Models\Product;
use App\Support\Student\StudentSettings;
use Illuminate\Database\Eloquent\Builder;

/**
 * What parents can pre-order: selling products in the categories the school put
 * on the pre-order menu (Settings → Student Settings), each with its weekly menu
 * (Students → Canteen Menu) when it has one. The price shown is today's price —
 * the till charges whatever the price is on the day.
 *
 * Read-only, so it returns the value directly.
 */
class MenuAction
{
    /** @return array<int, array{id: int, name: string, items: array<int, array>}> grouped by category */
    public function execute(): array
    {
        $settings = StudentSettings::current();
        if (! $settings->preOrdersOpen()) {
            return [];
        }

        $products = self::query($settings)
            ->with(['mainCategory:id,name', 'subCategory:id,name', 'images' => fn ($q) => $q->select('id', 'product_id', 'path', 'method')])
            ->orderBy('name')
            ->get(['id', 'type', 'code', 'name', 'barcode', 'mrp', 'tax', 'thumbnail', 'main_category_id', 'sub_category_id']);

        $menus = self::menus($products->pluck('id')->all());

        return $products
            ->groupBy(fn (Product $product) => self::menuCategory($product, $settings)?->id ?? 0)
            ->map(function ($items) use ($settings, $menus) {
                $category = self::menuCategory($items->first(), $settings);

                return [
                    'id' => $category?->id ?? 0,
                    'name' => $category?->name ?? 'Other',
                    'items' => $items->map(fn (Product $product) => self::product($product) + self::weekly($menus->get($product->id), $settings))->values()->all(),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    /** Selling products on the menu. */
    public static function query(StudentSettings $settings): Builder
    {
        $ids = $settings->preOrderCategoryIds;

        return Product::query()
            ->where('is_selling', true)
            ->where('status', 'active')
            ->where('type', 'product')
            ->where(fn ($q) => $q->whereIn('main_category_id', $ids ?: [0])->orWhereIn('sub_category_id', $ids ?: [0]));
    }

    /** One product as the portal and the till need it. */
    public static function product(Product $product): array
    {
        $image = $product->thumbnail;
        if (! $image && $product->relationLoaded('images')) {
            $image = ($product->images->firstWhere('method', 'normal') ?? $product->images->first())?->url;
        }

        return [
            'id' => $product->id,
            'type' => $product->type,
            'code' => $product->code,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'mrp' => round((float) $product->mrp, 2),
            'tax' => (float) $product->tax,
            'thumbnail' => $image ?: null,
            'main_category' => $product->relationLoaded('mainCategory') && $product->mainCategory
                ? ['id' => $product->mainCategory->id, 'name' => $product->mainCategory->name]
                : null,
        ];
    }

    /** @return \Illuminate\Support\Collection<int, CanteenMenu> keyed by product id */
    public static function menus(array $productIds)
    {
        return $productIds ? CanteenMenu::whereIn('product_id', $productIds)->get()->keyBy('product_id') : collect();
    }

    /** Whether a product is served on an ISO weekday: always, unless its menu names the days. */
    public static function servedOn(?CanteenMenu $menu, int $weekday): bool
    {
        $served = $menu?->servedWeekdays();

        return $served === null || in_array($weekday, $served, true);
    }

    /**
     * The weekly menu part of a menu item: the days it is served (null = every
     * school day) and each served school day's dishes.
     */
    private static function weekly(?CanteenMenu $menu, StudentSettings $settings): array
    {
        $served = $menu?->servedWeekdays();
        $days = [];
        foreach ($settings->schoolDays as $weekday) {
            if ($menu && ($served === null || in_array($weekday, $served, true))) {
                $days[] = ['weekday' => $weekday, 'dishes' => $menu->dishesFor($weekday)];
            }
        }

        return [
            'served_weekdays' => $served === null ? null : array_values(array_intersect($served, $settings->schoolDays)),
            'menu' => $days,
        ];
    }

    /** The menu category a product is listed under: its sub category when that is on the menu, else its main one. */
    private static function menuCategory(Product $product, StudentSettings $settings)
    {
        return in_array((int) $product->sub_category_id, $settings->preOrderCategoryIds, true) && $product->subCategory
            ? $product->subCategory
            : $product->mainCategory;
    }
}
