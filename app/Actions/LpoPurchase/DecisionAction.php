<?php

namespace App\Actions\LpoPurchase;

use App\Actions\Purchase\JournalEntryAction;
use App\Enums\Purchase\PurchaseStatus;
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
                $purchase->update([
                    'status' => PurchaseStatus::ACCEPTED->value,
                    'decision_by' => $userId,
                    'decision_at' => now(),
                    'decision_note' => $note,
                ]);

                // Create journal entries only (no stock update — GRN handles stock)
                $purchase->load(['account', 'items', 'payments.paymentMethod']);
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
}
