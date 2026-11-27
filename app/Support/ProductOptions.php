<?php

namespace App\Support;

use App\Models\OptionGroup;
use App\Models\OptionGroupItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The option groups a till asks for each product, ready to send: every group
 * with its rule and options, and the follow-up group an option opens nested
 * inside it (Red Bull → Sugar choice), down to OptionGroup::MAX_DEPTH.
 *
 * A variant without groups of its own asks its family head's groups, so a
 * shirt family shares one "Gift wrap" setting.
 */
final class ProductOptions
{
    /**
     * @param  iterable<Product>  $products
     * @return array<int, list<array<string, mixed>>> product id => groups (products without groups are left out)
     */
    public static function forProducts(iterable $products): array
    {
        $products = collect($products)->filter();
        if ($products->isEmpty()) {
            return [];
        }

        $ids = $products->flatMap(fn (Product $product) => array_filter([$product->id, $product->parent_id]))->unique()->values();
        $links = DB::table('product_option_groups')
            ->join('option_groups', 'option_groups.id', '=', 'product_option_groups.option_group_id')
            ->whereIn('product_option_groups.product_id', $ids)
            ->where('option_groups.status', 'active')
            ->where('option_groups.tenant_id', OptionGroup::getCurrentTenantId())
            ->orderBy('product_option_groups.sort')
            ->orderBy('product_option_groups.id')
            ->get(['product_option_groups.product_id', 'product_option_groups.option_group_id', 'product_option_groups.min_select', 'product_option_groups.max_select'])
            ->groupBy('product_id');
        if ($links->isEmpty()) {
            return [];
        }

        $groups = self::loadGroups($links->flatten(1)->pluck('option_group_id')->unique()->all());

        $result = [];
        foreach ($products as $product) {
            $own = $links->get($product->id) ?? $links->get($product->parent_id);
            if (! $own) {
                continue;
            }
            $payload = [];
            foreach ($own as $link) {
                $group = self::payload((int) $link->option_group_id, $groups, 0, []);
                if (! $group) {
                    continue;
                }
                if ($link->min_select !== null || $link->max_select !== null) {
                    $group['min'] = $link->min_select !== null ? (int) $link->min_select : $group['min'];
                    $group['max'] = $link->max_select !== null ? (int) $link->max_select : $group['max'];
                    $group['rule'] = OptionGroup::ruleLabel($group['min'], $group['max']);
                }
                $payload[] = $group;
            }
            if ($payload) {
                $result[$product->id] = $payload;
            }
        }

        return $result;
    }

    /** @return list<array<string, mixed>> the groups one product asks */
    public static function forProduct(Product $product): array
    {
        return self::forProducts([$product])[$product->id] ?? [];
    }

    /**
     * Every group reachable from [$rootIds] through follow-up groups, with its
     * active options, in as many queries as there are levels.
     *
     * @param  array<int, int>  $rootIds
     * @return Collection<int, OptionGroup>
     */
    private static function loadGroups(array $rootIds): Collection
    {
        $loaded = collect();
        $pending = $rootIds;
        for ($depth = 0; $pending && $depth <= OptionGroup::MAX_DEPTH; $depth++) {
            $batch = OptionGroup::query()->active()->whereIn('id', $pending)
                ->with(['items' => fn ($query) => $query->active()])
                ->get()->keyBy('id');
            $loaded = $loaded->union($batch);
            $pending = $batch->flatMap(fn (OptionGroup $group) => $group->items->pluck('next_group_id'))
                ->filter()->unique()->reject(fn ($id) => $loaded->has($id))->values()->all();
        }

        return $loaded;
    }

    /**
     * @param  Collection<int, OptionGroup>  $groups
     * @param  array<int, int>  $path  groups already open above this one (stops a loop)
     * @return array<string, mixed>|null
     */
    private static function payload(int $groupId, Collection $groups, int $depth, array $path): ?array
    {
        $group = $groups->get($groupId);
        if (! $group || $group->items->isEmpty() || in_array($groupId, $path, true)) {
            return null;
        }
        $path[] = $groupId;

        return [
            'id' => $group->id,
            'name' => $group->name,
            'min' => (int) $group->min_select,
            'max' => $group->max_select === null ? null : (int) $group->max_select,
            'rule' => OptionGroup::ruleLabel((int) $group->min_select, $group->max_select),
            'items' => $group->items->map(fn (OptionGroupItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => round((float) $item->price, 4),
                'is_default' => (bool) $item->is_default,
                'next' => $item->next_group_id && $depth + 1 < OptionGroup::MAX_DEPTH
                    ? self::payload((int) $item->next_group_id, $groups, $depth + 1, $path)
                    : null,
            ])->values()->all(),
        ];
    }
}
