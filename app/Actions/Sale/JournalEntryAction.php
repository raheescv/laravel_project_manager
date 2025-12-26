<?php

namespace App\Actions\Sale;

use App\Actions\Journal\CreateAction;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public $userId;

    public function execute(Sale $sale, $userId)
    {
        try {
            $this->userId = $userId;
            $data = [
                'date' => $sale->date,
                'branch_id' => $sale->branch_id,
                'description' => 'Sale:'.$sale->invoice_no,
                'reference_no' => $sale->reference_no,
                'source' => 'sale',
                'model' => 'Sale',
                'model_id' => $sale->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);

            $entries = [];

            // Sale Journal Entries
            if ($sale->gross_amount > 0) {
                $remarks = 'Sale to '.$sale->account->name;
                $debit = 0;
                $credit = $sale->gross_amount;
                $entries[] = $this->makeEntryPair($accounts['sale'], $sale->account_id, $debit, $credit, $remarks);
            }

            // Cost of Goods Sold
            $totalCost = $sale->items()->with('product', 'inventory')
                ->get()
                ->filter(fn ($item) => $item->product?->type === 'product')
                ->sum(fn ($item) => $item->inventory->cost * $item->quantity);

            if ($totalCost > 0) {
                $remarks = 'Cost of goods sold (Inventory transfer)';
                $debit = $totalCost;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['cost_of_goods_sold'], $accounts['inventory'], $debit, $credit, $remarks);
            }

            // Tax Entries
            if ($sale->tax_amount > 0) {
                $remarks = 'Sales tax collected on sale';
                $debit = 0;
                $credit = $sale->tax_amount;
                $entries[] = $this->makeEntryPair($accounts['tax_amount'], $sale->account_id, $debit, $credit, $remarks);
            }

            // Discounts
            if ($sale->item_discount > 0) {
                $remarks = 'Discount on individual product on sale';
                $debit = $sale->item_discount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['discount'], $sale->account_id, $debit, $credit, $remarks);
            }

            if ($sale->other_discount > 0) {
                $remarks = Sale::ADDITIONAL_DISCOUNT_DESCRIPTION;
                $debit = $sale->other_discount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accounts['discount'], $sale->account_id, $debit, $credit, $remarks);
            }

            // Freight
            if ($sale->freight > 0) {
                $remarks = 'Freight Charge provided on sale';
                $debit = 0;
                $credit = $sale->freight;
                $entries[] = $this->makeEntryPair($accounts['freight'], $sale->account_id, $debit, $credit, $remarks);
            }

            // Round Off
            if (abs($sale->round_off) > 0) {
                $remarks = 'Round Off adjustment';
                // If round_off is positive, it's an extra credit to customer; if negative, it's a debit
                if ($sale->round_off > 0) {
                    $debit = 0;
                    $credit = $sale->round_off;
                } else {
                    $debit = abs($sale->round_off);
                    $credit = 0;
                }
                $entries[] = $this->makeEntryPair($accounts['round_off'], $sale->account_id, $debit, $credit, $remarks);
            }
            // Payments
            foreach ($sale->payments as $payment) {
                $remarks = $payment->paymentMethod->name.' payment made by '.$sale->account->name;
                $debit = $payment->amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($payment->payment_method_id, $sale->account_id, $debit, $credit, $remarks, 'SalePayment', $payment->id);
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
