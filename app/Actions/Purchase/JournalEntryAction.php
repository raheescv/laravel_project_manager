<?php

namespace App\Actions\Purchase;

use App\Actions\Journal\CreateAction;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public $userId;

    public function execute($purchase, $userId)
    {
        try {
            $this->userId = $userId;

            $data = [
                'tenant_id' => $purchase->tenant_id,
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

            if (empty($accounts['tax_amount']) || empty($accounts['discount']) || empty($accounts['freight'])) {
                throw new \Exception('Required account heads are missing for purchase journal posting.');
            }

            $entries = [];

            $purchaseAccountId = $purchase->local_purchase_order_id ? ($accounts['unbilled_payables'] ?? null) : ($accounts['inventory'] ?? null);
            if (! $purchaseAccountId) {
                throw new \Exception('Required account heads are missing for purchase journal posting.');
            }

            // Purchase Entry — service lines are debited to their own expense
            // account (there is no inventory/GRN movement to clear), while goods
            // lines keep the default header account: Unbilled Payables for LPO
            // bills (so the GRNI clearing nets to zero against the GRN) or
            // Inventory for direct purchases. Lines with no expense account set
            // fall back to that same default.
            if ($purchase->gross_amount > 0) {
                $serviceProductIds = Product::whereIn('id', $purchase->items->pluck('product_id')->unique())
                    ->where('type', 'service')
                    ->pluck('id')
                    ->all();

                $debitByAccount = [];
                foreach ($purchase->items as $item) {
                    if ($item->total <= 0) {
                        continue;
                    }
                    $isServiceExpense = in_array($item->product_id, $serviceProductIds) && $item->account_id;
                    $debitAccountId = $isServiceExpense ? $item->account_id : $purchaseAccountId;
                    $debitByAccount[$debitAccountId] = ($debitByAccount[$debitAccountId] ?? 0) + (float) $item->total;
                }

                $remarks = 'Purchase from '.$purchase->account->name;
                foreach ($debitByAccount as $accountId => $amount) {
                    $entries[] = $this->makeEntryPair($accountId, $purchase->account_id, $amount, 0, $remarks, 'Purchase', $purchase->id);
                }
            }

            // Tax Entry
            if ($purchase->tax_amount > 0) {
                $remarks = 'Purchases tax collected on purchase';
                $debit = $purchase->tax_amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['tax_amount'], $purchase->account_id, $debit, $credit, $remarks, 'Purchase', $purchase->id);
            }

            // Item Discount Entry
            if ($purchase->item_discount > 0) {
                $remarks = 'Discount granted on individual product on purchase';
                $debit = 0;
                $credit = $purchase->item_discount;
                $entries[] = $this->makeEntryPair($accounts['discount'], $purchase->account_id, $debit, $credit, $remarks, 'Purchase', $purchase->id);
            }

            // Other Discount Entry
            if ($purchase->other_discount > 0) {
                $remarks = 'Additional Discount granted on purchase';
                $debit = 0;
                $credit = $purchase->other_discount;
                $entries[] = $this->makeEntryPair($accounts['discount'], $purchase->account_id, $debit, $credit, $remarks, 'Purchase', $purchase->id);
            }

            // Freight Entry
            if ($purchase->freight > 0) {
                $remarks = 'Freight charge on purchased goods';
                $debit = $purchase->freight;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['freight'], $purchase->account_id, $debit, $credit, $remarks, 'Purchase', $purchase->id);
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
