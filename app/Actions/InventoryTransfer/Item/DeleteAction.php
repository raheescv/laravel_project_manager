<?php

namespace App\Actions\InventoryTransfer\Item;

use App\Actions\InventoryTransfer\StockUpdateAction;
use App\Models\InventoryTransferItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $userId = Auth::id();
            $model = InventoryTransferItem::find($id);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $id.", 1);
            }
            if ($model->inventoryTransfer->status == 'completed') {
                if (! Auth::user()->can('inventory transfer.edit completed')) {
                    throw new Exception("You don't have permission to edit it.", 1);
                }
                $toInventory = (new StockUpdateAction())->toBranchCheckFunction($model->inventory, $model->inventoryTransfer->to_branch_id, $userId);
                (new StockUpdateAction())->singleItemFunction($toInventory, $model->quantity, $model->inventory_transfer_id, 'delete_item_from', $userId);

                (new StockUpdateAction())->singleItemFunction($model->inventory, $model->quantity, $model->inventory_transfer_id, 'delete_item_to', $userId);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the InventoryTransferItem. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update InventoryTransferItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
