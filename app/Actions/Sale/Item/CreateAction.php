<?php

namespace App\Actions\Sale\Item;

use App\Actions\Sale\Item\Option\SyncAction;
use App\Models\Configuration;
use App\Models\SaleItem;
use App\Support\Sale\SaleItemOptions;
use Exception;

class CreateAction
{
    use ValidatesUnitPriceAgainstMrpTrait;

    public function execute(array $data, int $user_id): array
    {
        try {
            $this->setUserIds($data, $user_id);
            $options = SaleItemOptions::forLine($data) ?? ['rows' => [], 'extra' => 0.0];
            $this->validateDuplicate($data, (bool) $options['rows']);
            $this->validateUnitPriceAgainstMrp($data, $options['extra']);
            validationHelper(SaleItem::rules(), $data);

            $model = SaleItem::create($data);

            if ($options['rows']) {
                $response = (new SyncAction())->execute($model, $options['rows']);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleItem';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function setUserIds(array &$data, int $user_id): void
    {
        $data['created_by'] = $data['updated_by'] = $user_id;
    }

    /** Lines with options never merge: two Paw-er Drinks in different flavours are two lines. */
    private function validateDuplicate(array $data, bool $hasOptions): void
    {
        if ($hasOptions || (Configuration::where('key', 'sale_item_row_mode')->value('value') ?? 'merge') === 'separate') {
            return;
        }

        $duplicate = SaleItem::where('product_id', $data['product_id'])
            ->where('employee_id', $data['employee_id'])
            ->where('sale_id', $data['sale_id'])
            ->whereDoesntHave('options')
            ->exists();

        if ($duplicate) {
            throw new Exception('Item already exists for this product under employee.', 1);
        }
    }
}
