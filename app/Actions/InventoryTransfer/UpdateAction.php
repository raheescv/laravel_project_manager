<?php

namespace App\Actions\InventoryTransfer;

use App\Actions\InventoryTransfer\Item\CreateAction as ItemCreateAction;
use App\Actions\InventoryTransfer\Item\UpdateAction as ItemUpdateAction;
use App\Models\InventoryTransfer;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $inventoryTransferId, $userId)
    {
        try {
            $model = InventoryTransfer::find($inventoryTransferId);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $inventoryTransferId.", 1);
            }
            if ($data['status'] == 'completed') {
                $data['approved_by'] = $userId;
                $data['approved_at'] = now();
            }
            $data['updated_by'] = $userId;

            // if it is edit after complete
            $oldStatus = $model->status;
            if ($oldStatus == 'completed') {
                if (! Auth::user()->can('inventory transfer.edit completed')) {
                    throw new Exception("You don't have permission to edit it.", 1);
                }
                $response = (new StockUpdateAction())->execute($model, $userId, 'transfer_reversal');
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            validationHelper(InventoryTransfer::rules($inventoryTransferId), $data);
            $model->update($data);

            foreach ($data['items'] as $value) {
                $value['inventory_transfer_id'] = $inventoryTransferId;

                if (isset($value['id'])) {
                    $response = (new ItemUpdateAction())->execute($value, $value['id'], $userId);
                } else {
                    $response = (new ItemCreateAction())->execute($value, $userId);
                }

                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            if ($model['status'] == 'completed') {
                $response = (new StockUpdateAction())->execute($model, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update InventoryTransfer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
