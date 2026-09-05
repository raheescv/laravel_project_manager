<?php

namespace App\Actions\Purchase\Item;

use App\Models\Configuration;
use App\Models\Product;
use App\Models\PurchaseItem;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            if ((Configuration::where('key', 'purchase_item_row_mode')->value('value') ?? 'merge') !== 'separate') {
                $duplicate = PurchaseItem::where('product_id', $data['product_id'])->where('purchase_id', $data['purchase_id'])->exists();
                if ($duplicate) {
                    $productName = Product::where('id', $data['product_id'])->value('name');
                    throw new \Exception($productName ? "Item already exists for this product: {$productName}" : 'Item already exists for this product.', 1);
                }
            }

            validationHelper(PurchaseItem::rules(), $data);
            $model = PurchaseItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created PurchaseItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
