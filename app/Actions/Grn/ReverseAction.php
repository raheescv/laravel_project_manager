<?php

namespace App\Actions\Grn;

use App\Actions\Purchase\JournalDeleteAction;
use App\Enums\Grn\GrnStatus;
use App\Enums\Purchase\PurchaseStatus;
use App\Models\Grn;
use App\Models\Purchase;

class ReverseAction
{
    public function execute(Grn $grn, int $userId, ?string $note = null)
    {
        try {
            if ($grn->status !== GrnStatus::ACCEPTED) {
                throw new \Exception('Only an accepted GRN can be reversed.');
            }

            // Protect the 3-way match: if the received value has already been billed
            // on an accepted LPO purchase, reversing the GRN credit would leave the
            // Unbilled Payables (GRNI) account unbalanced. Reverse the bill first.
            $hasAcceptedBill = Purchase::where('local_purchase_order_id', $grn->local_purchase_order_id)
                ->where('status', PurchaseStatus::ACCEPTED->value)
                ->exists();
            if ($hasAcceptedBill) {
                throw new \Exception('Cannot reverse: an accepted LPO purchase (bill) exists for this order. Reverse the bill first.');
            }

            // Reverse the inventory movement that was posted on accept.
            $grn->load(['items.product']);
            $stockResponse = (new StockUpdateAction())->execute($grn, $userId, 'reversal');
            if (! $stockResponse['success']) {
                throw new \Exception('GRN reversal failed at stock step: '.$stockResponse['message']);
            }

            // Reverse (delete) the GRN journal entries.
            $journalResponse = (new JournalDeleteAction())->execute($grn, $userId);
            if (! $journalResponse['success']) {
                throw new \Exception('GRN reversal failed at journal step: '.$journalResponse['message']);
            }

            $grn->update([
                'status' => GrnStatus::REVERSED,
                'decision_by' => $userId,
                'decision_at' => now(),
                'decision_note' => $note,
            ]);

            return [
                'success' => true,
                'message' => 'GRN reversed successfully. Stock and journal entries rolled back.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
