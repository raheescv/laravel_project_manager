<?php

namespace App\Actions\InventoryTransfer;

use App\Models\InventoryTransfer;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $data['created_by'] ?? $userId;
            $data['updated_by'] = $data['updated_by'] ?? $userId;

            if ($data['status'] == 'completed') {
                $data['approved_by'] = $data['approved_by'] ?? $userId;
                $data['approved_at'] = $data['approved_at'] ?? now();
            }
            validationHelper(InventoryTransfer::rules(), $data);
            $model = InventoryTransfer::create($data);

            foreach ($data['items'] as $value) {
                $value['inventory_transfer_id'] = $model->id;
                $response = (new Item\CreateAction())->execute($value, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            if ($model['status'] == 'completed') {
                $response = (new StockUpdateAction())->execute($model, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created InventoryTransfer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
