<?php

namespace App\Actions\PurchaseReturn;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\PurchaseReturn\Payment\CreateAction as PurchasePaymentCreateAction;
use App\Events\PurchaseReturnUpdatedEvent;
use App\Models\Account;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;

class PaymentAction
{
    public function execute($account_id, $name, $purchase_return_id, $data, $paymentData, $user_id)
    {
        try {
            $paymentMethod = Account::find($paymentData['payment_method_id']);
            $model = PurchaseReturn::find($purchase_return_id);

            $payment = $data['payment'];
            $discount = $data['discount'];

            $journalData = [
                'date' => $paymentData['date'],
                'created_by' => $user_id,
                'description' => 'PurchaseReturn:'.$model->invoice_no,
                'remarks' => $paymentData['remarks'],
                'reference_no' => $model->reference_no,
                'model' => 'PurchaseReturn',
                'model_id' => $purchase_return_id,
            ];

            $entries = [];

            if ($discount > 0) {
                $remarks = 'Additional Discount granted on PurchaseReturn';
                $discountAccountId = DB::table('accounts')->where('name', 'Discount')->value('id');

                $entries[] = [
                    'account_id' => $discountAccountId,
                    'counter_account_id' => $account_id,
                    'debit' => 0,
                    'credit' => $discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'counter_account_id' => $discountAccountId,
                    'debit' => $discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($payment) {
                $purchasePaymentData = [
                    'purchase_return_id' => $purchase_return_id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'date' => $paymentData['date'],
                    'amount' => $payment,
                ];

                $purchasePaymentResponse = (new PurchasePaymentCreateAction())->execute($purchasePaymentData, $user_id);
                $purchasePaymentId = $purchasePaymentResponse['data']['id'];

                $remarks = $paymentMethod->name.' payment made by '.$name;

                $entries[] = [
                    'account_id' => $paymentData['payment_method_id'],
                    'counter_account_id' => $account_id,
                    'debit' => 0,
                    'credit' => $payment,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'PurchasePayment',
                    'model_id' => $purchasePaymentId,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'counter_account_id' => $paymentData['payment_method_id'],
                    'debit' => $payment,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'PurchasePayment',
                    'model_id' => $purchasePaymentId,
                ];
            }

            $journalData['entries'] = $entries;

            $response = (new JournalCreateAction())->execute($journalData);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            if ($payment) {
                event(new PurchaseReturnUpdatedEvent('payment', $model));
            }

            if ($discount) {
                event(new PurchaseReturnUpdatedEvent('discount', $model));
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
