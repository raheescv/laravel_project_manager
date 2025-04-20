<?php

namespace App\Actions\SaleReturn;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\SaleReturn\Payment\CreateAction as SaleReturnPaymentCreateAction;
use App\Events\SaleReturnUpdatedEvent;
use App\Models\Account;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class PaymentAction
{
    public function execute($account_id, $name, $model_id, $data, $paymentData, $user_id)
    {
        try {
            $paymentMethod = Account::find($paymentData['payment_method_id']);
            $payment = $data['payment'];
            $discount = $data['discount'];
            $model = SaleReturn::find($model_id);

            $journalData['date'] = $paymentData['date'];
            $journalData['created_by'] = $user_id;
            $journalData['description'] = 'SaleReturn:'.$model->id;
            $journalData['remarks'] = $paymentData['remarks'];
            $journalData['reference_no'] = $model->reference_no;
            $journalData['model'] = 'SaleReturn';
            $journalData['model_id'] = $model_id;

            $entries = [];

            if ($discount > 0) {
                $remarks = SaleReturn::ADDITIONAL_DISCOUNT_DESCRIPTION;
                $discountAccountId = DB::table('accounts')->where('name', 'Discount')->value('id');
                $entries[] = [
                    'account_id' => $discountAccountId,
                    'counter_account_id' => $account_id,
                    'debit' => $discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'counter_account_id' => $discountAccountId,
                    'debit' => 0,
                    'credit' => $discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }
            if ($payment) {
                $salePaymentData = [
                    'sale_return_id' => $model_id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'date' => $paymentData['date'],
                    'amount' => $data['payment'],
                ];
                $salePaymentResponse = (new SaleReturnPaymentCreateAction())->execute($salePaymentData, $user_id);
                $sale_payment_id = $salePaymentResponse['data']['id'];
                $remarks = $paymentMethod['name'].' payment made by '.$name;
                $entries[] = [
                    'account_id' => $paymentData['payment_method_id'],
                    'counter_account_id' => $account_id,
                    'debit' => 0,
                    'credit' => $payment,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'SaleReturnPayment',
                    'model_id' => $sale_payment_id,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'counter_account_id' => $paymentData['payment_method_id'],
                    'debit' => $payment,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'SaleReturnPayment',
                    'model_id' => $sale_payment_id,
                ];
            }
            $journalData['entries'] = $entries;
            $response = (new JournalCreateAction())->execute($journalData);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            if ($payment) {
                event(new SaleReturnUpdatedEvent('payment', $model));
            }

            if ($discount) {
                event(new SaleReturnUpdatedEvent('discount', $model));
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Journal';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
