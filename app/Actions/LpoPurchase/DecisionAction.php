<?php

namespace App\Actions\LpoPurchase;

use App\Actions\Purchase\JournalEntryAction;
use App\Enums\Grn\GrnStatus;
use App\Enums\Purchase\PurchaseStatus;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\Purchase;

class DecisionAction
{
    public function execute(Purchase $purchase, string $action, int $userId, ?string $note = null)
    {
        try {
            if ($purchase->status !== PurchaseStatus::PENDING->value) {
                throw new \Exception('This purchase has already been decided.');
            }

            if ($action === 'accept') {
                $purchase->load(['account', 'items', 'payments.paymentMethod']);

                // 3-way match guard: cannot bill more than has actually been received
                // via accepted GRNs, otherwise Unbilled Payables never clears to zero.
                $this->assertBilledWithinReceived($purchase);

                $purchase->update([
                    'status' => PurchaseStatus::ACCEPTED->value,
                    'decision_by' => $userId,
                    'decision_at' => now(),
                    'decision_note' => $note,
                ]);

                // Create journal entries only (no stock update — GRN handles stock)
                $purchase->refresh();
                $journalResponse = (new JournalEntryAction())->execute($purchase, $userId);

                if (! $journalResponse['success']) {
                    throw new \Exception('Purchase approved but journal entry failed: '.$journalResponse['message']);
                }

                return [
                    'success' => true,
                    'message' => 'LPO Purchase accepted successfully.',
                ];
            } elseif ($action === 'reject') {
                if (empty($note) || strlen($note) < 3) {
                    throw new \Exception('Remarks are required for rejection (minimum 3 characters).');
                }

                $purchase->update([
                    'status' => PurchaseStatus::REJECTED->value,
                    'decision_by' => $userId,
                    'decision_at' => now(),
                    'decision_note' => $note,
                ]);

                return [
                    'success' => true,
                    'message' => 'LPO Purchase rejected.',
                ];
            }

            throw new \Exception('Invalid action.');
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Guard the 3-way match: for each product, the quantity being billed on this
     * LPO purchase must not exceed the quantity already received through accepted
     * GRNs for the same LPO. Over-billing would leave a permanent residual in the
     * Unbilled Payables (GRNI) clearing account.
     */
    private function assertBilledWithinReceived(Purchase $purchase): void
    {
        if (! $purchase->local_purchase_order_id) {
            return;
        }

        $receivedByProduct = GrnItem::whereHas('grn', function ($q) use ($purchase) {
            $q->where('local_purchase_order_id', $purchase->local_purchase_order_id)
                ->where('status', GrnStatus::ACCEPTED->value);
        })
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $billedByProduct = $purchase->items
            ->groupBy('product_id')
            ->map(fn ($items) => $items->sum('quantity'));

        foreach ($billedByProduct as $productId => $billedQty) {
            $receivedQty = (float) ($receivedByProduct[$productId] ?? 0);
            if (round($billedQty, 4) > round($receivedQty, 4)) {
                $productName = Product::whereKey($productId)->value('name') ?? "#$productId";
                throw new \Exception(
                    "Cannot accept: billed quantity ($billedQty) for \"$productName\" exceeds received quantity ($receivedQty). Receive the goods via an accepted GRN first."
                );
            }
        }
    }
}
