<?php

namespace App\Actions\Sale;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\Sale\Payment\CreateAction as SalePaymentCreateAction;
use App\Events\SaleUpdatedEvent;
use App\Models\Account;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReceiptAction
{
    public function execute($account_id, $name, $sale_id, $data, $paymentData, $user_id)
    {
        try {
            $paymentMethod = Account::find($paymentData['payment_method_id']);
            $payment = $data['payment'];
            $discount = $data['discount'];
            $sale = Sale::find($sale_id);

            $journalData['date'] = $paymentData['date'];
            $journalData['created_by'] = $user_id;
            $journalData['description'] = 'Sale:'.$sale->invoice_no;
            $journalData['remarks'] = $paymentData['remarks'];
            $journalData['reference_no'] = $sale->reference_no;
            $journalData['model'] = 'Sale';
            $journalData['model_id'] = $sale_id;

            $entries = [];

            if ($discount > 0) {
                $remarks = 'Additional Discount provided on sale';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => $discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'debit' => 0,
                    'credit' => $discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }
            if ($payment) {
                $salePaymentData = [
                    'sale_id' => $sale_id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'date' => $paymentData['date'],
                    'amount' => $data['payment'],
                ];
                $salePaymentResponse = (new SalePaymentCreateAction())->execute($salePaymentData, $user_id);
                $sale_payment_id = $salePaymentResponse['data']['id'];
                $remarks = $paymentMethod['name'].' payment made by '.$name;
                $entries[] = [
                    'account_id' => $paymentData['payment_method_id'],
                    'debit' => $payment,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'SalePayment',
                    'model_id' => $sale_payment_id,
                ];
                $entries[] = [
                    'account_id' => $account_id,
                    'debit' => 0,
                    'credit' => $payment,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'SalePayment',
                    'model_id' => $sale_payment_id,
                ];
            }
            $journalData['entries'] = $entries;
            $response = (new JournalCreateAction())->execute($journalData);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            if ($payment) {
                event(new SaleUpdatedEvent('payment', $sale));
            }

            if ($discount) {
                event(new SaleUpdatedEvent('discount', $sale));
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
