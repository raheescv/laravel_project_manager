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
            $payment = $data['payment'];
            $discount = $data['discount'];
            $purchase = Purchase::find($purchase_id);

            $journalData['date'] = $paymentData['date'];
            $journalData['created_by'] = $user_id;
            $journalData['description'] = 'Purchase:'.$purchase->invoice_no;
            $journalData['remarks'] = $paymentData['remarks'];
            $journalData['reference_no'] = $purchase->reference_no;
            $journalData['model'] = 'Purchase';
            $journalData['model_id'] = $purchase_id;

            $entries = [];

            if ($discount > 0) {
                $remarks = 'Additional Discount granted on Purchase';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => 0,
                    'credit' => $discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $account_id,
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
                    'amount' => $data['payment'],
                ];
                $purchasePaymentResponse = (new PurchasePaymentCreateAction())->execute($purchasePaymentData, $user_id);
                $purchase_payment_id = $purchasePaymentResponse['data']['id'];
                $remarks = $paymentMethod['name'].' payment made by '.$name;
                $entries[] = [
                    'account_id' => $paymentData['payment_method_id'],
                    'debit' => 0,
                    'credit' => $payment,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'PurchasePayment',
                    'model_id' => $purchase_payment_id,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'debit' => $payment,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'PurchasePayment',
                    'model_id' => $purchase_payment_id,
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
