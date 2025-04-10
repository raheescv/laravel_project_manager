<?php

namespace App\Actions\Sale\Item;

use App\Actions\Sale\StockUpdateAction;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SaleItem::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if ($model->sale->status == 'completed') {
                (new StockUpdateAction())->singleItemDelete($model, $model->sale, 'delete_item', Auth::id());
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the SaleItem. Please try again.', 1);
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
