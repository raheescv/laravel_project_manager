<?php

namespace App\Actions\Tailoring;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Models\Journal;
use App\Models\TailoringOrder;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    /**
     * Remove all existing journals for this order (used before creating a new one on update).
     */
    public function deleteJournalsForOrder(TailoringOrder $order, int $userId): void
    {
        $journals = Journal::withoutGlobalScopes()->where('model', 'TailoringOrder')->where('model_id', $order->id)->get();

        foreach ($journals as $journal) {
            $journal->entries()->update(['deleted_by' => $userId]);
            $journal->entries()->delete();
            $journal->update(['deleted_by' => $userId]);
            $journal->delete();
        }
    }

    /**
     * Create one journal for the tailoring order (same pattern as Sale).
     * Called from CreateTailoringOrderAction / UpdateTailoringOrderAction when status is completed.
     */
    public function executeForOrder(TailoringOrder $order, int $userId): array
    {
        try {
            $order->loadMissing(['account', 'payments.paymentMethod']);
            $accounts = Cache::get('accounts_slug_id_map', []);
            $saleAccountId = $accounts['sale'] ?? null;
            if (! $saleAccountId || ! $order->account_id) {
                return ['success' => true, 'message' => 'Journal skipped (missing sale or customer account).'];
            }

            $customerName = $order->customer_name ?: $order->account?->name ?? '';
            $grandTotal = (float) ($order->grand_total ?? ($order->total ?? 0));
            $taxAmount = (float) ($order->tax_amount ?? 0);
            $itemDiscount = (float) ($order->item_discount ?? 0);
            $otherDiscount = (float) ($order->other_discount ?? 0);
            $totalDiscount = $itemDiscount + $otherDiscount;
            if ($grandTotal <= 0) {
                return ['success' => true, 'message' => 'Journal skipped (no amount).'];
            }

            $data = [
                'tenant_id' => $order->tenant_id,
                'date' => $order->order_date?->format('Y-m-d') ?? date('Y-m-d'),
                'branch_id' => $order->branch_id,
                'description' => 'Tailoring Order: '.$order->order_no,
                'remarks' => 'Tailoring order '.$order->order_no,
                'reference_number' => $order->order_no,
                'person_name' => $customerName,
                'source' => 'Tailoring Order',
                'model' => 'TailoringOrder',
                'model_id' => $order->id,
                'created_by' => $userId,
            ];

            $entries = [];
            $taxAccountId = $accounts['tax_amount'] ?? null;
            $discountAccountId = $accounts['discount'] ?? null;
            $grossAndStitch = (float) ($order->gross_amount ?? 0) + (float) ($order->stitch_amount ?? 0);

            // Revenue: Dr Customer, Cr Sale (gross + stitch when posting discount/tax separately, else grand_total)
            $postDiscountSeparately = $totalDiscount > 0 && $discountAccountId;
            $postTaxSeparately = $taxAmount > 0 && $taxAccountId;
            $revenueAmount = $postDiscountSeparately || $postTaxSeparately ? $grossAndStitch : $grandTotal;
            if ($revenueAmount > 0) {
                $entries = array_merge($entries, $this->makeEntryPair($saleAccountId, $order->account_id, 0, $revenueAmount, 'Tailoring order '.$order->order_no.' - '.$customerName, 'TailoringOrder', $order->id, $userId));
            }

            // Discount: Dr Discount, Cr Customer (item + other discount)
            if ($itemDiscount > 0 && $discountAccountId) {
                $entries = array_merge($entries, $this->makeEntryPair($discountAccountId, $order->account_id, $itemDiscount, 0, 'Item discount on tailoring order '.$order->order_no, 'TailoringOrder', $order->id, $userId));
            }
            if ($otherDiscount > 0 && $discountAccountId) {
                $entries = array_merge($entries, $this->makeEntryPair($discountAccountId, $order->account_id, $otherDiscount, 0, 'Additional discount on tailoring order '.$order->order_no, 'TailoringOrder', $order->id, $userId));
            }

            // Tax: Dr Customer, Cr Tax account (sales tax collected on order)
            if ($taxAmount > 0 && $taxAccountId) {
                $entries = array_merge($entries, $this->makeEntryPair($taxAccountId, $order->account_id, 0, $taxAmount, 'Sales tax on tailoring order '.$order->order_no, 'TailoringOrder', $order->id, $userId));
            }

            // Round off
            $roundOff = (float) ($order->round_off ?? 0);
            if (abs($roundOff) > 0) {
                $roundOffAccountId = $accounts['round_off'] ?? null;
                if ($roundOffAccountId) {
                    $remarks = 'Round off adjustment - tailoring order '.$order->order_no;
                    if ($roundOff > 0) {
                        $entries = array_merge($entries, $this->makeEntryPair($roundOffAccountId, $order->account_id, 0, $roundOff, $remarks, 'TailoringOrder', $order->id, $userId));
                    } else {
                        $entries = array_merge($entries, $this->makeEntryPair($roundOffAccountId, $order->account_id, abs($roundOff), 0, $remarks, 'TailoringOrder', $order->id, $userId));
                    }
                }
            }

            // Payments: Dr Payment method, Cr Customer
            foreach ($order->payments as $payment) {
                $remarks = ($payment->paymentMethod?->name ?? 'Payment').' payment by '.$customerName;
                $entries = array_merge($entries, $this->makeEntryPair($payment->payment_method_id, $order->account_id, (float) $payment->amount, 0, $remarks, 'TailoringPayment', $payment->id, $userId));
            }

            $data['entries'] = $entries;
            $response = (new JournalCreateAction())->execute($data);
            if (! ($response['success'] ?? false)) {
                throw new \Exception($response['message'] ?? 'Failed to create journal');
            }

            return ['success' => true, 'message' => 'Successfully created journal.', 'data' => $response['data'] ?? []];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $model, $modelId, $userId): array
    {
        $base = [
            'created_by' => $userId,
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
