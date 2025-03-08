<?php

namespace App\Actions\Purchase;

use App\Actions\Purchase\Item\CreateAction as ItemCreateAction;
use App\Actions\Purchase\Payment\CreateAction as PaymentCreateAction;
use App\Models\Purchase;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['branch_id'] = session('branch_id', 1);
            $data['created_by'] = $user_id;

            validationHelper(Purchase::rules(), $data);
            $model = Purchase::create($data);
            foreach ($data['items'] as $key => $value) {
                $value['purchase_id'] = $model->id;
                $response = (new ItemCreateAction)->execute($value, $user_id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            foreach ($data['payments'] as $value) {
                $value['purchase_id'] = $model->id;
                $value['date'] = $model->date;
                $response = (new PaymentCreateAction)->execute($value, $user_id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            if ($model['status'] == 'completed') {
                $response = (new StockUpdateAction)->execute($model, $user_id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
                $model->refresh();
                $response = (new JournalEntryAction)->execute($model, $user_id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Purchase';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
