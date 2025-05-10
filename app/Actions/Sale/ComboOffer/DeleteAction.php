<?php

namespace App\Actions\Sale\ComboOffer;

use App\Models\Sale;
use App\Models\SaleComboOffer;
use App\Models\SaleItem;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SaleComboOffer::find($id);
            if (! $model) {
                throw new Exception("SaleComboOffer not found with the specified ID: $id.", 1);
            }
            SaleItem::where('sale_combo_offer_id', $id)->update(['sale_combo_offer_id' => null, 'discount' => 0]);
            $model->refresh();

            Sale::where('id', $model->sale_id)->update([
                'gross_amount' => $model->sale->items->sum('gross_amount'),
                'item_discount' => $model->sale->items->sum('discount'),
                'tax_amount' => $model->sale->items->sum('tax_amount'),
            ]);

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SaleComboOffer. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleComboOffer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
