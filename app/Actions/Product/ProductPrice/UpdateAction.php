<?php

namespace App\Actions\Product\ProductPrice;

use App\Models\ProductPrice;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = ProductPrice::find($id);
            if (! $model) {
                throw new \Exception("ProductPrice not found with the specified ID: $id.", 1);
            }
            if ($data['price_type'] != 'offer') {
                unset($data['start_date']);
                unset($data['end_date']);
            } else {
                if (! $data['start_date']) {
                    throw new \Exception('start date required', 1);
                }
                if (! $data['end_date']) {
                    throw new \Exception('end date required', 1);
                }
            }
            validationHelper(ProductPrice::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductPrice';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
