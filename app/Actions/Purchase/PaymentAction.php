<?php

namespace App\Actions\Purchase;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\Purchase\Payment\CreateAction as PurchasePaymentCreateAction;
use App\Events\PurchaseUpdatedEvent;
use App\Models\Account;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PaymentAction
{
    public function execute($account_id, $name, $purchase_id, $data, $paymentData, $user_id)
    {
        try {
            $paymentMethod = Account::find($paymentData['payment_method_id']);
            $purchase = Purchase::find($purchase_id);

            $payment = $data['payment'];
            $discount = $data['discount'];

            $journalData = [
                'date' => $paymentData['date'],
                'created_by' => $user_id,
                'description' => 'Purchase:'.$purchase->invoice_no,
                'remarks' => $paymentData['remarks'],
                'reference_no' => $purchase->reference_no,
                'model' => 'Purchase',
                'model_id' => $purchase_id,
            ];

            $entries = [];

            if ($discount > 0) {
                $remarks = 'Additional Discount granted on Purchase';
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
                    'purchase_id' => $purchase_id,
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
                event(new PurchaseUpdatedEvent('payment', $purchase));
            }

            if ($discount) {
                event(new PurchaseUpdatedEvent('discount', $purchase));
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
