<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Product::find($id);
            if (! $model) {
                throw new Exception("Product not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Product. Please try again.', 1);
            }

            $saleItemCount = SaleItem::where('product_id', $id)->count();
            if ($saleItemCount) {
                throw new Exception('product already used in sales.', 1);
            }

            $saleReturnItemCount = SaleReturnItem::where('product_id', $id)->count();
            if ($saleReturnItemCount) {
                throw new Exception('product already used in sales return.', 1);
            }

            $purchaseItemCount = PurchaseItem::where('product_id', $id)->count();
            if ($purchaseItemCount) {
                throw new Exception('product already used in Purchases.', 1);
            }

            $model->inventories()->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully Update Product';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
