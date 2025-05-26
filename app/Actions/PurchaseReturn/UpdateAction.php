<?php

namespace App\Actions\PurchaseReturn;

use App\Actions\Journal\DeleteAction;
use App\Models\PurchaseReturn;
use Exception;

class UpdateAction
{
    public function execute($data, $purchase_return_id, $user_id)
    {
        try {
            $model = PurchaseReturn::find($purchase_return_id);
            if (! $model) {
                throw new Exception("PurchaseReturn not found with the specified ID: $purchase_return_id.", 1);
            }

            if ($data['status'] == 'cancelled') {
                $data['cancelled_by'] = $user_id;
            } else {
                $data['updated_by'] = $user_id;
            }

            validationHelper(PurchaseReturn::rules($purchase_return_id), $data);
            $model->update($data);
            if ($data['status'] != 'cancelled') {

                foreach ($data['items'] as $value) {
                    $value['purchase_return_id'] = $purchase_return_id;

                    if (isset($value['id'])) {
                        $response = (new Item\UpdateAction())->execute($value, $value['id'], $user_id);
                    } else {
                        $response = (new Item\CreateAction())->execute($value, $user_id);
                    }

                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                foreach ($data['payments'] as $value) {
                    $value['purchase_return_id'] = $purchase_return_id;

                    if (isset($value['id'])) {
                        $response = (new Payment\UpdateAction())->execute($value, $value['id'], $user_id);
                    } else {
                        $value['date'] = $model->date;
                        $response = (new Payment\CreateAction())->execute($value, $user_id);
                    }

                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                if ($model['status'] == 'completed') {
                    $response = (new StockUpdateAction())->execute($model, $user_id);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                    $model->refresh();
                    $response = (new JournalEntryAction())->execute($model, $user_id);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }
            } else {
                $response = (new StockUpdateAction())->execute($model, $user_id, false);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                foreach ($model->journals as $journal) {
                    $response = (new DeleteAction())->execute($journal->id, $user_id);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseReturn';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
