<?php

namespace App\Actions\Tailoring;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\Tailoring\Payment\CreateAction as PaymentCreateAction;
use App\Models\Account;
use App\Models\TailoringOrder;
use Exception;

class ReceiptAction
{
    public function execute($orderId, $customerName, $amount, $paymentData, $userId)
    {
        try {
            $order = TailoringOrder::find($orderId);
            if (! $order) {
                throw new Exception('Order not found.');
            }

            $paymentData = [
                'tailoring_order_id' => $orderId,
                'payment_method_id' => $paymentData['payment_method_id'],
                'date' => $paymentData['date'],
                'amount' => $amount,
            ];
            $response = (new PaymentCreateAction())->execute($paymentData, $userId);

            if (! ($response['success'] ?? false)) {
                throw new Exception($response['message'] ?? 'Failed to create payment');
            }

            $payment = $response['data'];
            $paymentId = $payment->id ?? null;

            if ($order->account_id && $amount > 0) {
                $paymentMethod = Account::find($paymentData['payment_method_id']);
                $remarks = ($paymentMethod ? $paymentMethod->name : 'Payment').' payment made by '.$customerName;

                $journalData = [
                    'tenant_id' => $order->tenant_id,
                    'branch_id' => $order->branch_id ?? session('branch_id'),
                    'date' => $paymentData['date'],
                    'description' => 'Tailoring: '.$order->order_no,
                    'remarks' => $paymentData['remarks'] ?? '',
                    'reference_number' => $order->order_no,
                    'person_name' => $customerName,
                    'source' => 'Tailoring Receipt',
                    'model' => 'TailoringPayment',
                    'model_id' => $paymentId,
                    'created_by' => $userId,
                    'entries' => [
                        [
                            'account_id' => $paymentData['payment_method_id'],
                            'counter_account_id' => $order->account_id,
                            'debit' => $amount,
                            'credit' => 0,
                            'created_by' => $userId,
                            'remarks' => $remarks,
                            'model' => 'TailoringPayment',
                            'model_id' => $paymentId,
                        ],
                        [
                            'account_id' => $order->account_id,
                            'counter_account_id' => $paymentData['payment_method_id'],
                            'debit' => 0,
                            'credit' => $amount,
                            'created_by' => $userId,
                            'remarks' => $remarks,
                            'model' => 'TailoringPayment',
                            'model_id' => $paymentId,
                        ],
                    ],
                ];
                $journalResponse = (new JournalCreateAction())->execute($journalData);
                if (! ($journalResponse['success'] ?? false)) {
                    throw new Exception($journalResponse['message'] ?? 'Failed to create journal');
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully recorded payment';
            $return['data'] = $payment;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
