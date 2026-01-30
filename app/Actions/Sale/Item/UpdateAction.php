<?php

namespace App\Actions\Sale\Item;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SaleItem;
use Exception;

class UpdateAction
{
    use ValidatesUnitPriceAgainstMrpTrait;

    public function execute(array $data, int $id, int $user_id): array
    {
        try {
            $model = $this->findSaleItem($id);
            $this->prepareDataForUpdate($data, $model, $user_id);
            $this->validateUnitPriceAgainstMrp($data);
            $this->preserveQuantityForAuditLog($data, $model);

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

    private function findSaleItem(int $id): SaleItem
    {
        $model = SaleItem::find($id);

        if (! $model) {
            throw new Exception("SaleItem not found with the specified ID: $id.", 1);
        }

        return $model;
    }

    private function prepareDataForUpdate(array &$data, SaleItem $model, int $user_id): void
    {
        $data['updated_by'] = $user_id;
        $data['created_by'] = $model->created_by;
    }

    private function preserveQuantityForAuditLog(array &$data, SaleItem $model): void
    {
        // To avoid storing the audit log when quantity hasn't changed
        if ($model->quantity == $data['quantity']) {
            $data['quantity'] = $model->quantity;
        }
    }

    /**
     * Override the trait method to use relationship-based lookup for UpdateAction
     */
    protected function findProductUnit(Product $product, int $unitId): ?ProductUnit
    {
        return $product->units->firstWhere('id', $unitId);
    }
}
