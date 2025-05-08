<?php

namespace App\Actions\Sale\Package;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePackage;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SalePackage::find($id);
            if (! $model) {
                throw new Exception("SalePackage not found with the specified ID: $id.", 1);
            }
            SaleItem::where('sale_package_id', $id)->update(['sale_package_id' => null, 'discount' => 0]);
            $model->refresh();

            Sale::where('id', $model->sale_id)->update([
                'gross_amount' => $model->sale->items->sum('gross_amount'),
                'item_discount' => $model->sale->items->sum('discount'),
                'tax_amount' => $model->sale->items->sum('tax_amount'),
            ]);

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SalePackage. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SalePackage';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
