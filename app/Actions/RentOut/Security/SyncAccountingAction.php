<?php

namespace App\Actions\RentOut\Security;

use App\Enums\RentOut\SecurityStatus;
use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOutSecurity;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;

class SyncAccountingAction
{
    /**
     * Reconcile the ledger (journals + payment transactions) for a security
     * deposit so it matches its current status. Idempotent — safe to call on
     * every create/update; it wipes prior entries and recreates what is needed.
     *
     *  - Collected / Adjusted : collection receipt (Dr Payment Method, Cr Security Deposit)
     *  - Returned             : collection receipt + refund payout (Dr Security Deposit, Cr Payment Method)
     *  - Pending              : no ledger movement
     */
    public function execute(RentOutSecurity $security, ?int $userId = null): array
    {
        try {
            $userId ??= Auth::id();

            $this->reverseExisting($security);

            $status = $security->status instanceof SecurityStatus
                ? $security->status
                : SecurityStatus::tryFrom((string) $security->status);

            $hasCashLeg = $security->account_id && (float) $security->amount > 0;

            $needsCollection = $hasCashLeg && in_array($status, [
                SecurityStatus::Collected,
                SecurityStatus::Returned,
                SecurityStatus::Adjusted,
            ], true);

            $needsRefund = $hasCashLeg && $status === SecurityStatus::Returned;

            if ($needsCollection) {
                $response = RentOutTransactionHelper::storeSecurityCollection($security, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            if ($needsRefund) {
                $response = RentOutTransactionHelper::storeSecurityRefund($security, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            return ['success' => true, 'message' => 'Security accounting synced', 'data' => []];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Soft-delete any existing payment transactions + journals for this security.
     * Global scopes are bypassed so entries are removed regardless of the active
     * branch context.
     */
    public function reverseExisting(RentOutSecurity $security): void
    {
        $transactions = RentOutTransaction::where('model', 'RentOutSecurity')
            ->where('model_id', $security->id)
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->journal_id) {
                JournalEntry::withoutGlobalScopes()->where('journal_id', $transaction->journal_id)->delete();
                Journal::withoutGlobalScopes()->where('id', $transaction->journal_id)->delete();
            }
            $transaction->delete();
        }
    }
}
