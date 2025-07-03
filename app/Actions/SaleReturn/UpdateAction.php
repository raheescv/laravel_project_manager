<?php

namespace App\Actions\SaleReturn;

use App\Actions\Journal\DeleteAction;
use App\Actions\SaleReturn\Item\CreateAction as ItemCreateAction;
use App\Actions\SaleReturn\Item\UpdateAction as ItemUpdateAction;
use App\Actions\SaleReturn\Payment\CreateAction as PaymentCreateAction;
use App\Actions\SaleReturn\Payment\UpdateAction as PaymentUpdateAction;
use App\Models\SaleReturn;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $saleReturnId, $user_id)
    {
        try {
            $model = SaleReturn::find($saleReturnId);
            if (! $model) {
                throw new Exception("Sale Return not found with the specified ID: $saleReturnId.", 1);
            }

            $data['updated_by'] = $user_id;

            // if it is edit after complete
            $oldStatus = $model->status;
            if ($oldStatus == 'completed') {
                if (! Auth::user()->can('sales return.edit completed')) {
                    throw new Exception("You don't have permission to edit it.", 1);
                }
                $response = (new JournalDeleteAction())->execute($model, $user_id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $response = (new StockUpdateAction())->execute($model, $user_id, 'sale_return_reversal');
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            validationHelper(SaleReturn::rules($saleReturnId), $data);
            $model->update($data);
            if ($data['status'] != 'cancelled') {

                foreach ($data['items'] as $value) {
                    $value['sale_return_id'] = $saleReturnId;

                    if (isset($value['id'])) {
                        $response = (new ItemUpdateAction())->execute($value, $value['id'], $user_id);
                    } else {
                        $response = (new ItemCreateAction())->execute($value, $user_id);
                    }

                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                foreach ($data['payments'] as $value) {
                    $value['sale_return_id'] = $saleReturnId;

                    if (isset($value['id'])) {
                        $response = (new PaymentUpdateAction())->execute($value, $value['id'], $user_id);
                    } else {
                        $value['date'] = $model->date;
                        $response = (new PaymentCreateAction())->execute($value, $user_id);
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
                $response = (new StockUpdateAction())->execute($model, $user_id, 'cancel');
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                if ($model->journals) {
                    foreach ($model->journals as $journal) {
                        $response = (new DeleteAction())->execute($journal->id, $user_id);
                        if (! $response['success']) {
                            throw new Exception($response['message'], 1);
                        }
                    }
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Sale';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
