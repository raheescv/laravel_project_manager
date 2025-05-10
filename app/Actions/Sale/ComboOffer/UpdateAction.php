<?php

namespace App\Actions\Sale\ComboOffer;

use App\Models\SaleComboOffer;
use App\Models\SaleItem;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = SaleComboOffer::find($id);
            if (! $model) {
                throw new \Exception("SaleComboOffer not found with the specified ID: $id.", 1);
            }

            validationHelper(SaleComboOffer::rules($id), $data);

            $model->update($data);

            SaleItem::where('sale_combo_offer_id', $model->id)->update(['sale_combo_offer_id' => null]);

            foreach ($data['items'] as $value) {
                SaleComboOffer::addComboOfferId($data['sale_id'], $value['inventory_id'], $value['employee_id'], $model->id);
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
