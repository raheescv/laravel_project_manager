<?php

namespace App\Actions\Sale\Item;

use App\Models\Configuration;
use App\Models\Product;
use App\Models\SaleItem;
use Exception;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            $duplicate = SaleItem::where('product_id', $data['product_id'])->where('employee_id', $data['employee_id'])->where('sale_id', $data['sale_id'])->exists();
            if ($duplicate) {
                throw new Exception('Item already exists for this product under employee.', 1);
            }
            $validateUnitPriceAgainstMrp = Configuration::where('key', 'validate_unit_price_against_mrp')->value('value') ?? 'yes';
            if ($validateUnitPriceAgainstMrp === 'yes') {
                $product = Product::find($data['product_id']);
                if ($product->type == 'product' && $data['unit_price'] > $product->mrp) {
                    throw new Exception('Unit price cannot be greater than MRP.', 1);
                }
            }
            validationHelper(SaleItem::rules(), $data);
            $model = SaleItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleItem';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
