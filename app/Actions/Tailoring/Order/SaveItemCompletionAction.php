<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrderItem;
use Exception;

class SaveItemCompletionAction
{
    /**
     * Update a single item's completion and return refreshed item with relations.
     *
     * @param  int  $itemId
     * @param  array  $data
     * @param  int  $userId
     * @return array
     */
    public function execute($itemId, $data, $userId)
    {
        try {
            $item = TailoringOrderItem::findOrFail($itemId);
            $order = $item->order()->firstOrFail();

            (new ProcessOrderCompletionItemsAction())->execute($order, [array_merge(['id' => (int) $item->id], $data)], (int) $userId);

            $updatedItem = (new CompletionDataLoader())->loadItem($item);

            $return['success'] = true;
            $return['message'] = 'Item completion updated successfully';
            $return['data'] = $updatedItem;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
