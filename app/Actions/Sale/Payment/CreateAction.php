<?php

namespace App\Actions\Sale\Payment;

use App\Models\Account;
use App\Models\Sale;
use App\Models\SalePayment;

class CreateAction
{
    public function execute(array $data, int $user_id): array
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(SalePayment::rules(), $data);
            $model = SalePayment::create($data);

            // Update sale payment methods
            $this->updateSalePaymentMethods($model->sale);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SalePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public function updateSalePaymentMethods(Sale $sale): void
    {
        $payment_method_ids = $sale->payments->pluck('payment_method_id')->toArray();
        $payment_method_name = Account::whereIn('id', $payment_method_ids)
            ->pluck('name')
            ->toArray();

        $data = [
            'payment_method_ids' => implode(',', $payment_method_ids),
            'payment_method_name' => implode(', ', $payment_method_name),
        ];
        $sale->update($data);
    }
}
