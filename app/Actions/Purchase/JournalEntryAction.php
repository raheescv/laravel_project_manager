<?php

namespace App\Actions\Purchase;

use App\Actions\Journal\CreateAction;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public $userId;

    public function execute($purchase, $userId)
    {
        try {
            $this->userId = $userId;

            $data = [
                'date' => $purchase->date,
                'branch_id' => $purchase->branch_id,
                'description' => 'Purchase:'.$purchase->invoice_no,
                'reference_no' => $purchase->reference_no,
                'source' => 'purchase',
                'model' => 'Purchase',
                'model_id' => $purchase->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);

            $entries = [];

            // Inventory Entry
            if ($purchase->gross_amount > 0) {
                $remarks = 'Purchase from '.$purchase->account->name;
                $debit = $purchase->gross_amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['inventory'], $purchase->account_id, $debit, $credit, $remarks);
            }

            // Tax Entry
            if ($purchase->tax_amount > 0) {
                $remarks = 'Purchases tax collected on purchase';
                $debit = $purchase->tax_amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['tax_amount'], $purchase->account_id, $debit, $credit, $remarks);
            }

            // Item Discount Entry
            if ($purchase->item_discount > 0) {
                $remarks = 'Discount granted on individual product on purchase';
                $debit = 0;
                $credit = $purchase->item_discount;
                $entries[] = $this->makeEntryPair($accounts['discount'], $purchase->account_id, $debit, $credit, $remarks);
            }

            // Other Discount Entry
            if ($purchase->other_discount > 0) {
                $remarks = 'Additional Discount granted on purchase';
                $debit = 0;
                $credit = $purchase->other_discount;
                $entries[] = $this->makeEntryPair($accounts['discount'], $purchase->account_id, $debit, $credit, $remarks);
            }

            // Freight Entry
            if ($purchase->freight > 0) {
                $remarks = 'Freight charge on purchased goods';
                $debit = $purchase->freight;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['freight'], $purchase->account_id, $debit, $credit, $remarks);
            }

            // Payment Entries
            foreach ($purchase->payments as $payment) {
                $remarks = $payment->paymentMethod->name.' payment made by '.$purchase->account->name;
                $debit = 0;
                $credit = $payment->amount;
                $entries[] = $this->makeEntryPair($payment->payment_method_id, $purchase->account_id, $debit, $credit, $remarks, 'PurchasePayment', $payment->id);
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
