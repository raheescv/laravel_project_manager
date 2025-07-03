<?php

namespace App\Actions\SaleReturn\Item;

use App\Actions\SaleReturn\StockUpdateAction;
use App\Models\SaleReturnItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SaleReturnItem::find($id);
            if (! $model) {
                throw new Exception("Sale Return Item not found with the specified ID: $id.", 1);
            }
            if ($model->saleReturn->status == 'completed') {
                if (! Auth::user()->can('sales return.edit completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                (new StockUpdateAction())->singleItemDelete($model, $model->saleReturn, 'delete_item', Auth::id());
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SaleReturnItem. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleReturnItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
