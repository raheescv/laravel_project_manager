<?php

namespace App\Actions\Purchase\Item;

use App\Actions\Purchase\StockUpdateAction;
use App\Models\PurchaseItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PurchaseItem::find($id);
            if (! $model) {
                throw new \Exception("PurchaseItem not found with the specified ID: $id.", 1);
            }
            if ($model->purchase->status == 'completed') {
                if (! Auth::user()->can('purchase.delete item after completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                (new StockUpdateAction())->singleItem($model, $model->purchase, 'delete_item', Auth::id());
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the PurchaseItem. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
