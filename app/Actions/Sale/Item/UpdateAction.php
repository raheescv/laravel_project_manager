<?php

namespace App\Actions\Sale\Item;

use App\Models\Configuration;
use App\Models\Product;
use App\Models\SaleItem;
use Exception;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = SaleItem::find($id);
            if (! $model) {
                throw new Exception("SaleItem not found with the specified ID: $id.", 1);
            }
            $data['created_by'] = $model->created_by;

            $validateUnitPriceAgainstMrp = Configuration::where('key', 'validate_unit_price_against_mrp')->value('value') ?? 'yes';
            if ($validateUnitPriceAgainstMrp === 'yes') {
                $product = Product::find($data['product_id']);
                if ($product->type == 'product' && $data['unit_price'] > $product->mrp) {
                    throw new Exception('Unit price cannot be greater than MRP.', 1);
                }
            }

            // to avoid storing the audit log
            if ($model->quantity == $data['quantity']) {
                $data['quantity'] = $model->quantity;
            }
            validationHelper(SaleItem::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleItem';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
