<?php

namespace App\Actions\Sale\Item;

use App\Models\SaleItem;
use Exception;

class CreateAction
{
    use ValidatesUnitPriceAgainstMrpTrait;

    public function execute(array $data, int $user_id): array
    {
        try {
            $this->setUserIds($data, $user_id);
            $this->validateDuplicate($data);
            $this->validateUnitPriceAgainstMrp($data);
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

    private function setUserIds(array &$data, int $user_id): void
    {
        $data['created_by'] = $data['updated_by'] = $user_id;
    }

    private function validateDuplicate(array $data): void
    {
        $duplicate = SaleItem::where('product_id', $data['product_id'])
            ->where('employee_id', $data['employee_id'])
            ->where('sale_id', $data['sale_id'])
            ->exists();

        if ($duplicate) {
            throw new Exception('Item already exists for this product under employee.', 1);
        }
    }
}
