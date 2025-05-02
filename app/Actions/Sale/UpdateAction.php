<?php

namespace App\Actions\Sale;

use App\Actions\Journal\DeleteAction;
use App\Actions\Sale\Item\CreateAction as ItemCreateAction;
use App\Actions\Sale\Item\UpdateAction as ItemUpdateAction;
use App\Actions\Sale\Payment\CreateAction as PaymentCreateAction;
use App\Actions\Sale\Payment\UpdateAction as PaymentUpdateAction;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $sale_id, $user_id)
    {
        try {
            $model = Sale::find($sale_id);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $sale_id.", 1);
            }

            if ($data['status'] == 'cancelled') {
                $data['cancelled_by'] = $user_id;
            } else {
                $data['updated_by'] = $user_id;
            }

            // if it is edit after complete
            $oldStatus = $model->status;
            if ($oldStatus == 'completed') {
                if (! Auth::user()->can('sale.edit completed')) {
                    throw new Exception("You don't have permission to edit it.", 1);
                }
                $response = (new JournalDeleteAction())->execute($model, $user_id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $response = (new StockUpdateAction())->execute($model, $user_id, 'sale_reversal');
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            validationHelper(Sale::rules($sale_id), $data);

            // to avoid storing the audit log
            if ($model->gross_amount == $data['gross_amount']) {
                $data['gross_amount'] = $model->gross_amount;
            }
            if ($model->item_discount == $data['item_discount']) {
                $data['item_discount'] = $model->item_discount;
            }
            if ($model->tax_amount == $data['tax_amount']) {
                $data['tax_amount'] = $model->tax_amount;
            }
            if ($model->total == $data['total']) {
                $data['total'] = $model->total;
            }
            if ($model->paid == $data['paid']) {
                $data['paid'] = $model->paid;
            }

            $model->update($data);
            if ($data['status'] != 'cancelled') {

                foreach ($data['items'] as $value) {
                    $value['sale_id'] = $sale_id;

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
                    $value['sale_id'] = $sale_id;

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
