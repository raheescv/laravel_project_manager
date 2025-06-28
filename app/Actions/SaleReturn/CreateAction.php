<?php

namespace App\Actions\SaleReturn;

use App\Actions\SaleReturn\Item\CreateAction as ItemCreateAction;
use App\Actions\SaleReturn\Payment\CreateAction as PaymentCreateAction;
use App\Models\SaleReturn;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $user_id;
            $data['invoice_no'] = getNextSaleInvoiceNo();
            validationHelper(SaleReturn::rules(), $data);
            $model = SaleReturn::create($data);

            foreach ($data['items'] as $value) {
                $value['sale_return_id'] = $model->id;
                $response = (new ItemCreateAction())->execute($value, $user_id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            foreach ($data['payments'] as $value) {
                $value['sale_return_id'] = $model->id;
                $value['date'] = $model->date;
                $response = (new PaymentCreateAction())->execute($value, $user_id);
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

            $return['success'] = true;
            $return['message'] = 'Successfully Created Sale';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
