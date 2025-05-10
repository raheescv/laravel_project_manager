<?php

namespace App\Actions\Sale\ComboOffer;

use App\Models\SaleComboOffer;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(SaleComboOffer::rules(), $data);

            $model = SaleComboOffer::create($data);

            foreach ($data['items'] as $value) {
                SaleComboOffer::addComboOfferId($data['sale_id'], $value['inventory_id'], $value['employee_id'], $model->id);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleComboOffer';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
