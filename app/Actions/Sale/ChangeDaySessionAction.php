<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\TenantScope;
use Exception;

/**
 * Move a sale onto a different sale day session.
 *
 * Shared by the "Change Sale Day Session" modal (App\Livewire\Sale\ChangeSession)
 * and the bulk `sale:sync-day-sessions` command, so the accounting stays
 * identical whichever way a sale is moved.
 *
 * Following the rest of app/Actions/Sale, this action does NOT open a
 * transaction — the caller owns the boundary.
 */
class ChangeDaySessionAction
{
    public $model;

    public $userId;

    /**
     * @param  SaleDaySession|int  $session  The session to move the sale onto.
     * @param  array  $options  sync_dates: also move the sale/journal/payment dates
     *                          onto the new session's date (default true).
     *                          sync_amounts: adjust the frozen closing/expected
     *                          amounts of any closed session involved (default true).
     */
    public function execute(Sale $sale, $session, $userId = null, array $options = [])
    {
        $this->model = $sale;
        $this->userId = $userId;

        try {
            $newSession = $this->resolveSession($sale, $session);
            $oldSession = $sale->sale_day_session_id
                ? $this->sessionQuery($sale)->find($sale->sale_day_session_id)
                : null;

            $syncDates = $options['sync_dates'] ?? true;
            $syncAmounts = $options['sync_amounts'] ?? true;

            $data = ['sale_day_session_id' => $newSession->id];

            if ($syncDates) {
                $data['date'] = $newSession->opened_at->format('Y-m-d');
            }

            if ($this->userId) {
                $data['updated_by'] = $this->userId;
            }

            $sale->update($data);

            if ($syncDates) {
                $this->syncDates($sale, $data['date']);
            }

            if ($syncAmounts) {
                $this->syncAmounts($sale, $newSession, $oldSession);
            }

            $return['success'] = true;
            $return['message'] = 'Sale day session updated successfully.';
            $return['data'] = $sale;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * A session is only valid for this sale if it belongs to the same tenant and
     * the same branch — moving a sale across branches would corrupt both tills.
     */
    private function resolveSession(Sale $sale, $session): SaleDaySession
    {
        if (! $session) {
            throw new Exception('Please select a day session.', 1);
        }

        if (! $session instanceof SaleDaySession) {
            $session = $this->sessionQuery($sale)->find($session);
        }

        if (! $session) {
            throw new Exception('Invalid session selected.', 1);
        }

        if ((int) $session->tenant_id !== (int) $sale->tenant_id || (int) $session->branch_id !== (int) $sale->branch_id) {
            throw new Exception('The selected session belongs to a different branch.', 1);
        }

        return $session;
    }

    /**
     * Sessions are matched on the sale's own tenant and branch rather than on the
     * global scopes: the bulk command runs without an authenticated user, where
     * AssignedBranchScope is a silent no-op and TenantScope can latch onto the
     * TENANT_ID fallback.
     */
    private function sessionQuery(Sale $sale)
    {
        return SaleDaySession::withoutGlobalScopes([TenantScope::class, AssignedBranchScope::class])
            ->where('tenant_id', $sale->tenant_id)
            ->where('branch_id', $sale->branch_id);
    }

    /** The accounting has to land on the day the session belongs to, not the day the sale was rung up. */
    private function syncDates(Sale $sale, string $date): void
    {
        $sale->journals()->update(['date' => $date]);
        $sale->payments()->update(['date' => $date]);

        foreach ($sale->payments as $payment) {
            $payment->journalEntries()->update(['date' => $date]);
        }

        if ($sale->journal) {
            $sale->journal->entries()->update(['date' => $date]);
        }
    }

    /**
     * A closed session's closing/expected figures were frozen from the sales it
     * held at close time, so a completed sale moving in or out has to shift them.
     * Drafts and cancelled sales never counted towards those totals (see
     * SaleDaySession::sales(), which is completed-only), so they must not.
     */
    private function syncAmounts(Sale $sale, SaleDaySession $newSession, ?SaleDaySession $oldSession): void
    {
        if ($sale->status !== 'completed' || ! $sale->paid) {
            return;
        }

        if ($newSession->id == $oldSession?->id) {
            return;
        }

        if ($newSession->status == 'closed') {
            $newSession->update([
                'closing_amount' => $newSession->closing_amount + $sale->paid,
                'expected_amount' => $newSession->expected_amount + $sale->paid,
            ]);
        }

        if ($oldSession && $oldSession->status == 'closed') {
            $oldSession->update([
                'closing_amount' => $oldSession->closing_amount - $sale->paid,
                'expected_amount' => $oldSession->expected_amount - $sale->paid,
            ]);
        }
    }
}
