<?php

namespace App\Support\Sale;

use App\Models\OptionGroupItem;
use App\Models\Product;
use App\Support\ProductOptions;
use Exception;

/**
 * Checks the choices a till sent for one sale line against the product's
 * option groups and turns them into snapshot rows for sale_item_options.
 *
 * A pick is ['option_group_item_id' => 5] for a group the product asks, or
 * ['option_group_item_id' => 9, 'parent_option_group_item_id' => 5] for the
 * follow-up group option 5 opens. Prices and names always come from the
 * database, never from the client.
 */
final class SaleItemOptions
{
    /**
     * @param  array<int, array<string, mixed>>  $picks
     * @return array{rows: list<array<string, mixed>>, extra: float}
     *
     * @throws Exception when a pick is unknown, breaks a group's rule or a required group is missing
     */
    public static function resolve(Product $product, array $picks): array
    {
        $groups = ProductOptions::forProduct($product);
        $picks = array_values(array_filter($picks, fn ($pick) => is_array($pick) && ! empty($pick['option_group_item_id'])));
        if (! $groups) {
            if ($picks) {
                throw new Exception("{$product->name} has no options to choose.", 1);
            }

            return ['rows' => [], 'extra' => 0.0];
        }

        $known = OptionGroupItem::query()
            ->whereIn('id', array_map(fn ($pick) => (int) $pick['option_group_item_id'], $picks))
            ->active()
            ->get()->keyBy('id');

        $rows = [];
        $picked = [];
        foreach ($picks as $index => $pick) {
            $itemId = (int) $pick['option_group_item_id'];
            $parentId = (int) ($pick['parent_option_group_item_id'] ?? 0);
            $item = $known->get($itemId);
            $group = $parentId ? self::followUpOf($groups, $parentId) : self::topGroup($groups, $item?->option_group_id);
            if (! $item || ! $group || (int) $group['id'] !== (int) $item->option_group_id) {
                throw new Exception("{$product->name}: one of the chosen options is no longer offered. Pick the options again.", 1);
            }
            if ($parentId && ! isset($picked[$parentId])) {
                throw new Exception("{$product->name}: {$item->name} needs its main choice picked too.", 1);
            }
            $slot = $parentId.'|'.$group['id'];
            if (in_array($itemId, $picked[$slot] ?? [], true)) {
                throw new Exception("{$product->name}: {$item->name} is picked twice.", 1);
            }
            $picked[$slot][] = $itemId;
            $picked[$itemId] = true;

            $rows[] = [
                'key' => $itemId,
                'parent_key' => $parentId ?: null,
                'option_group_id' => (int) $group['id'],
                'option_group_item_id' => $itemId,
                'group_name' => $group['name'],
                'option_name' => $item->name,
                'price' => round((float) $item->price, 4),
                'product_id' => $item->product_id,
                'stock_quantity' => $item->product_id ? (float) $item->quantity : 0,
                'sort' => $index,
            ];
        }

        self::assertRules($product, $groups, $picked, 0);

        return ['rows' => $rows, 'extra' => round(array_sum(array_column($rows, 'price')), 4)];
    }

    /**
     * A sale line's picks, resolved against its product — or null when the
     * caller sent no `options` key (an older app), so nothing is checked and
     * the line keeps the picks it has.
     *
     * @param  array<string, mixed>  $data
     * @return ?array{rows: list<array<string, mixed>>, extra: float}
     *
     * @throws Exception when a pick is invalid
     */
    public static function forLine(array $data, ?int $fallbackProductId = null): ?array
    {
        if (! array_key_exists('options', $data) || ! ($product = Product::find($data['product_id'] ?? $fallbackProductId))) {
            return null;
        }

        return self::resolve($product, (array) $data['options']);
    }

    /**
     * Every group on screen needs between its min and max picks: the product's
     * own groups, and the follow-up group of each option picked.
     *
     * @param  list<array<string, mixed>>  $groups
     * @param  array<string|int, mixed>  $picked
     */
    private static function assertRules(Product $product, array $groups, array $picked, int $parentId): void
    {
        foreach ($groups as $group) {
            $chosen = $picked[$parentId.'|'.$group['id']] ?? [];
            $count = count($chosen);
            if ($count < $group['min']) {
                throw new Exception("{$product->name}: pick ".($group['min'] === 1 ? 'a' : $group['min']).' '.$group['name'].'.', 1);
            }
            if ($group['max'] !== null && $count > $group['max']) {
                throw new Exception("{$product->name}: pick no more than {$group['max']} {$group['name']}.", 1);
            }
            foreach ($group['items'] as $item) {
                if ($item['next'] && in_array($item['id'], $chosen, true)) {
                    self::assertRules($product, [$item['next']], $picked, (int) $item['id']);
                }
            }
        }
    }

    /** @param  list<array<string, mixed>>  $groups */
    private static function topGroup(array $groups, ?int $groupId): ?array
    {
        foreach ($groups as $group) {
            if ((int) $group['id'] === (int) $groupId) {
                return $group;
            }
        }

        return null;
    }

    /**
     * The follow-up group of option [$optionId], wherever it sits in the tree.
     *
     * @param  list<array<string, mixed>>  $groups
     */
    private static function followUpOf(array $groups, int $optionId): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if ((int) $item['id'] === $optionId) {
                    return $item['next'];
                }
                if ($item['next'] && ($found = self::followUpOf([$item['next']], $optionId))) {
                    return $found;
                }
            }
        }

        return null;
    }
}
