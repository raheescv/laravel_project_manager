<?php

namespace App\Actions\Sale\Item\Option;

use App\Models\SaleItem;
use App\Models\SaleItemOption;

/**
 * Replaces the choices stored on one sale line with [$rows] (as resolved by
 * App\Support\Sale\SaleItemOptions), linking each follow-up pick to the pick
 * that opened it.
 */
class SyncAction
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function execute(SaleItem $item, array $rows): array
    {
        try {
            SaleItemOption::where('sale_item_id', $item->id)->delete();

            $saved = [];
            foreach ($rows as $row) {
                $parentKey = $row['parent_key'] ?? null;
                $saved[$row['key']] = SaleItemOption::create([
                    'sale_item_id' => $item->id,
                    'parent_id' => $parentKey ? ($saved[$parentKey]->id ?? null) : null,
                    'option_group_id' => $row['option_group_id'],
                    'option_group_item_id' => $row['option_group_item_id'],
                    'group_name' => $row['group_name'],
                    'option_name' => $row['option_name'],
                    'price' => $row['price'],
                    'product_id' => $row['product_id'],
                    'stock_quantity' => $row['stock_quantity'],
                    'sort' => $row['sort'],
                ]);
            }

            $return['success'] = true;
            $return['message'] = 'Saved the options of the line';
            $return['data'] = array_values($saved);
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
