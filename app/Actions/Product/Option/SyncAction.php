<?php

namespace App\Actions\Product\Option;

use App\Models\OptionGroup;
use App\Models\Product;
use Exception;

/**
 * Sets which option groups the till asks for a product, in the order given.
 * $groups is a list of ['option_group_id' => 3, 'min_select' => ?int, 'max_select' => ?int];
 * a blank min / max keeps the group's own rule.
 */
class SyncAction
{
    /** @return array{success: bool, message: string, data?: mixed} */
    public function execute(int $productId, array $groups): array
    {
        try {
            $product = Product::find($productId);
            if (! $product) {
                throw new Exception("Product not found with the specified ID: {$productId}.", 1);
            }
            $ids = array_map(fn ($group) => (int) ($group['option_group_id'] ?? 0), $groups);
            if (count($ids) !== count(array_unique($ids))) {
                throw new Exception('A group is attached twice.', 1);
            }
            $known = OptionGroup::whereIn('id', $ids)->pluck('id')->all();
            if (array_diff($ids, $known)) {
                throw new Exception('One of the option groups no longer exists.', 1);
            }

            $sync = [];
            foreach (array_values($groups) as $sort => $group) {
                $min = ($group['min_select'] ?? '') === '' ? null : max(0, (int) $group['min_select']);
                $max = ($group['max_select'] ?? '') === '' ? null : max(1, (int) $group['max_select']);
                if ($min !== null && $max !== null && $max < $min) {
                    throw new Exception('The most picks cannot be fewer than the least.', 1);
                }
                $sync[(int) $group['option_group_id']] = [
                    'tenant_id' => $product->tenant_id,
                    'sort' => $sort,
                    'min_select' => $min,
                    'max_select' => $max,
                ];
            }
            $product->optionGroups()->sync($sync);

            $return['success'] = true;
            $return['message'] = 'Saved the options of '.$product->name;
            $return['data'] = $product->optionGroups()->get();
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
