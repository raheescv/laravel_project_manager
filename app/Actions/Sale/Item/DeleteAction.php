<?php

namespace App\Actions\Sale\Item;

use App\Actions\Sale\StockUpdateAction;
use App\Models\SaleItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SaleItem::find($id);
            if (! $model) {
                throw new Exception("SaleItem not found with the specified ID: $id.", 1);
            }
            if ($model->sale->status == 'completed') {
                if (! Auth::user()->can('sale.delete item after completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                (new StockUpdateAction())->singleItem($model, $model->sale, 'delete_item', Auth::id());
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SaleItem. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
