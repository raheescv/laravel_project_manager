<?php

namespace App\Actions\LpoPurchase;

use App\Actions\Purchase\JournalDeleteAction;
use App\Enums\Purchase\PurchaseStatus;
use App\Models\Purchase;

class ReverseAction
{
    public function execute(Purchase $purchase, int $userId, ?string $note = null)
    {
        try {
            if ($purchase->status !== PurchaseStatus::ACCEPTED->value) {
                throw new \Exception('Only an accepted LPO purchase can be reversed.');
            }

            // The bill posts journals only (GRN owns stock), so reversal just rolls
            // back the journal entries and returns the bill to a reversed state.
            $journalResponse = (new JournalDeleteAction())->execute($purchase, $userId);
            if (! $journalResponse['success']) {
                throw new \Exception('Reversal failed at journal step: '.$journalResponse['message']);
            }

            $purchase->update([
                'status' => PurchaseStatus::REVERSED->value,
                'decision_by' => $userId,
                'decision_at' => now(),
                'decision_note' => $note,
            ]);

            return [
                'success' => true,
                'message' => 'LPO Purchase reversed successfully. Journal entries rolled back.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
