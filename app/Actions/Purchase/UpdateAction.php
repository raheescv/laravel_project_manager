<?php

namespace App\Actions\Purchase;

use App\Actions\Journal\DeleteAction;
use App\Actions\Purchase\Item\CreateAction as ItemCreateAction;
use App\Actions\Purchase\Item\UpdateAction as ItemUpdateAction;
use App\Actions\Purchase\Payment\CreateAction as PaymentCreateAction;
use App\Actions\Purchase\Payment\UpdateAction as PaymentUpdateAction;
use App\Models\Purchase;

class UpdateAction
{
    public function execute($data, $purchase_id, $user_id)
    {
        try {
            $model = Purchase::find($purchase_id);
            if (! $model) {
                throw new \Exception("Purchase not found with the specified ID: $purchase_id.", 1);
            }

            if ($data['status'] == 'cancelled') {
                $data['cancelled_by'] = $user_id;
            } else {
                $data['updated_by'] = $user_id;
            }

            validationHelper(Purchase::rules($purchase_id), $data);
            $model->update($data);
            if ($data['status'] != 'cancelled') {

                foreach ($data['items'] as $value) {
                    $value['purchase_id'] = $purchase_id;

                    if (isset($value['id'])) {
                        $response = (new ItemUpdateAction())->execute($value, $value['id'], $user_id);
                    } else {
                        $response = (new ItemCreateAction())->execute($value, $user_id);
                    }

                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }

                foreach ($data['payments'] as $value) {
                    $value['purchase_id'] = $purchase_id;

                    if (isset($value['id'])) {
                        $response = (new PaymentUpdateAction())->execute($value, $value['id'], $user_id);
                    } else {
                        $value['date'] = $model->date;
                        $response = (new PaymentCreateAction())->execute($value, $user_id);
                    }

                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }

                if ($model['status'] == 'completed') {
                    $response = (new StockUpdateAction())->execute($model, $user_id);
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                    $model->refresh();
                    $response = (new JournalEntryAction())->execute($model, $user_id);
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }
            } else {
                $response = (new StockUpdateAction())->execute($model, $user_id, false);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
                foreach ($model->journals as $journal) {
                    $response = (new DeleteAction())->execute($journal->id, $user_id);
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Purchase';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
