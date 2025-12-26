<?php

namespace App\Actions\PurchaseReturn;

use App\Actions\Journal\CreateAction;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public $userId;

    public function execute($model, $userId)
    {
        try {
            $this->userId = $userId;

            $data = [
                'date' => $model->date,
                'branch_id' => $model->branch_id,
                'description' => 'PurchaseReturn:'.$model->invoice_no,
                'reference_no' => $model->invoice_no,
                'source' => 'purchase_return',
                'model' => 'PurchaseReturn',
                'model_id' => $model->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);

            $entries = [];

            // Inventory Entry
            if ($model->gross_amount > 0) {
                $remarks = 'PurchaseReturn from '.$model->account->name;
                $debit = 0;
                $credit = $model->gross_amount;
                $entries[] = $this->makeEntryPair($accounts['inventory'], $model->account_id, $debit, $credit, $remarks);
            }

            // Tax Entry
            if ($model->tax_amount > 0) {
                $remarks = 'Purchases tax collected on purchase return';
                $debit = 0;
                $credit = $model->tax_amount;
                $entries[] = $this->makeEntryPair($accounts['tax_amount'], $model->account_id, $debit, $credit, $remarks);
            }

            // Item Discount Entry
            if ($model->item_discount > 0) {
                $remarks = 'Discount granted on individual product on purchase return';
                $debit = $model->item_discount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['discount'], $model->account_id, $debit, $credit, $remarks);
            }

            // Other Discount Entry
            if ($model->other_discount > 0) {
                $remarks = 'Additional Discount granted on purchase return';
                $debit = $model->other_discount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['discount'], $model->account_id, $debit, $credit, $remarks);
            }

            // Freight Entry
            if ($model->freight > 0) {
                $remarks = 'Freight charge on purchase returned goods';
                $debit = 0;
                $credit = $model->freight;
                $entries[] = $this->makeEntryPair($accounts['freight'], $model->account_id, $debit, $credit, $remarks);
            }

            // Payment Entries
            foreach ($model->payments as $payment) {
                $remarks = $payment->paymentMethod->name.' payment made by '.$model->account->name;
                $debit = $payment->amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($payment->payment_method_id, $model->account_id, $debit, $credit, $remarks, 'PurchasePayment', $payment->id);
            }

            $data['entries'] = array_merge(...$entries);

            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            $return['success'] = true;
            $return['data'] = [];
            $return['message'] = 'Successfully Updated Journal';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $model = null, $modelId = null)
    {
        $base = [
            'created_by' => $this->userId,
            'remarks' => $remarks,
            'model' => $model,
            'model_id' => $modelId,
        ];

        return [
            array_merge($base, [
                'account_id' => $accountId1,
                'counter_account_id' => $accountId2,
                'debit' => $debit,
                'credit' => $credit,
            ]),
            array_merge($base, [
                'account_id' => $accountId2,
                'counter_account_id' => $accountId1,
                'debit' => $credit,
                'credit' => $debit,
            ]),
        ];
    }
}
