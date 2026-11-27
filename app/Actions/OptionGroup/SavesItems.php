<?php

namespace App\Actions\OptionGroup;

use App\Models\OptionGroup;
use App\Models\OptionGroupItem;
use App\Models\Product;
use Exception;

/**
 * Shared by the option group Create / Update actions: tidies the input and
 * writes the group's options in the order given.
 */
final class SavesItems
{
    /** Blank max means "no limit"; status defaults to active. */
    public static function normalise(array $data): array
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['min_select'] = (int) ($data['min_select'] ?? 0);
        $data['max_select'] = ($data['max_select'] ?? '') === '' || $data['max_select'] === null ? null : (int) $data['max_select'];
        $data['status'] = $data['status'] ?? 'active';
        $data['items'] = array_values(array_filter(
            (array) ($data['items'] ?? []),
            fn ($item) => is_array($item) && trim((string) ($item['name'] ?? '')) !== '',
        ));

        return $data;
    }

    /** @param  list<array<string, mixed>>  $items */
    public static function validateItems(array $items, int $groupId): void
    {
        if (! $items) {
            throw new Exception('Add at least one option.', 1);
        }
        $names = [];
        foreach ($items as $index => $item) {
            $item['price'] = ($item['price'] ?? '') === '' ? 0 : $item['price'];
            $item['quantity'] = ($item['quantity'] ?? '') === '' ? 1 : $item['quantity'];
            $label = trim((string) $item['name']) ?: 'Option '.($index + 1);
            try {
                validationHelper(OptionGroupItem::rules(), $item);
            } catch (\Throwable $th) {
                throw new Exception("{$label}: {$th->getMessage()}", 1);
            }
            $key = mb_strtolower(trim((string) $item['name']));
            if (isset($names[$key])) {
                throw new Exception("{$label} is listed twice.", 1);
            }
            $names[$key] = true;
            if (! empty($item['product_id']) && ! Product::whereKey($item['product_id'])->exists()) {
                throw new Exception("{$label}: the stock item no longer exists.", 1);
            }
            if (! empty($item['next_group_id'])) {
                if ($groupId && (int) $item['next_group_id'] === $groupId) {
                    throw new Exception("{$label} cannot open its own group.", 1);
                }
                if (! OptionGroup::whereKey($item['next_group_id'])->exists()) {
                    throw new Exception("{$label}: the follow-up group no longer exists.", 1);
                }
            }
        }
    }

    /** @param  list<array<string, mixed>>  $items */
    public static function sync(OptionGroup $group, array $items): void
    {
        $keep = [];
        foreach (array_values($items) as $sort => $item) {
            $values = [
                'option_group_id' => $group->id,
                'name' => trim((string) $item['name']),
                'price' => ($item['price'] ?? '') === '' ? 0 : (float) $item['price'],
                'product_id' => ($item['product_id'] ?? null) ?: null,
                'quantity' => ($item['quantity'] ?? '') === '' ? 1 : (float) $item['quantity'],
                'next_group_id' => ($item['next_group_id'] ?? null) ?: null,
                'is_default' => (bool) ($item['is_default'] ?? false),
                'sort' => $sort,
                'status' => $item['status'] ?? 'active',
            ];
            $existing = ! empty($item['id']) ? OptionGroupItem::where('option_group_id', $group->id)->find($item['id']) : null;
            if ($existing) {
                $existing->update($values);
                $keep[] = $existing->id;
            } else {
                $keep[] = OptionGroupItem::create($values)->id;
            }
        }
        OptionGroupItem::where('option_group_id', $group->id)->whereNotIn('id', $keep)->delete();
    }
}
