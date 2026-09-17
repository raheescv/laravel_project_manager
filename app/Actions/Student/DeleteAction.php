<?php

namespace App\Actions\Student;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Sale;
use Exception;

/**
 * Remove a student who was enrolled by mistake. The caller owns the transaction.
 *
 * A student whose account has ever been posted to (a top-up, a purchase) is part
 * of the books and cannot be deleted — set their status to inactive or graduated
 * instead, which keeps the statement and the balance.
 */
class DeleteAction
{
    public function execute(int $accountId, int $userId): array
    {
        try {
            $account = Account::student()->find($accountId);
            if (! $account) {
                throw new Exception("Student not found with the specified ID: $accountId.", 1);
            }

            $hasHistory = JournalEntry::where('account_id', $account->id)->exists()
                || Sale::withoutGlobalScopes()->where('tenant_id', $account->tenant_id)->where('account_id', $account->id)->exists();
            if ($hasHistory) {
                throw new Exception('This student already has card transactions, so the record cannot be deleted. Mark the student inactive instead.', 1);
            }

            $account->guardians()->detach();
            $account->studentDetail()->delete();

            if (! $account->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the student. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Student';
            $return['data'] = $account;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
