<?php

namespace App\Actions\PurchaseReturn;

use App\Models\PurchaseReturn;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $user_id;

            validationHelper(PurchaseReturn::rules(), $data);
            $model = PurchaseReturn::create($data);
            foreach ($data['items'] as $value) {
                $value['purchase_return_id'] = $model->id;
                $response = (new Item\CreateAction())->execute($value, $user_id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            foreach ($data['payments'] as $value) {
                $value['purchase_return_id'] = $model->id;
                $value['date'] = $model->date;
                $response = (new Payment\CreateAction())->execute($value, $user_id);
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
            $model->update([
                'gross_amount' => $model->items->sum('total'),
                'total_discount' => $model->items->sum('discount'),
                'tax_amount' => $model->items->sum('tax_amount'),
                'paid' => $model->payments->sum('amount'),
            ]);
            $return['success'] = true;
            $return['message'] = 'Successfully Created PurchaseReturn';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
