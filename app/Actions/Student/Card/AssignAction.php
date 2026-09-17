<?php

namespace App\Actions\Student\Card;

use App\Models\Account;
use App\Models\StudentDetail;
use Exception;

/**
 * Link an NFC card to a student, or replace a lost one.
 *
 * The balance lives on the student's account, not on the card, so a replacement
 * card spends the same money the moment it is linked. Linking always leaves the
 * card active — a replacement is how a block is cleared for a new card.
 */
class AssignAction
{
    public function execute(int $accountId, ?string $uid, int $userId): array
    {
        try {
            $detail = StudentDetail::where('account_id', $accountId)->first();
            if (! $detail || ! Account::student()->whereKey($accountId)->exists()) {
                throw new Exception("Student not found with the specified ID: $accountId.", 1);
            }

            $uid = StudentDetail::normalizeCardUid($uid);
            if (! $uid) {
                throw new Exception('Tap or enter the card number to link it.', 1);
            }

            $holder = StudentDetail::with('account:id,name')->where('card_uid', $uid)->where('id', '!=', $detail->id)->first();
            if ($holder) {
                throw new Exception("This card is already linked to {$holder->account?->name} ({$holder->admission_no}). Unlink it there first.", 1);
            }

            $detail->fill([
                'card_uid' => $uid,
                'card_status' => StudentDetail::CARD_ACTIVE,
                'card_blocked_at' => null,
                'card_blocked_by_type' => null,
                'card_blocked_by_id' => null,
                'card_block_reason' => null,
                'updated_by' => $userId,
            ])->save();

            $return['success'] = true;
            $return['message'] = 'Card linked to the student';
            $return['data'] = $detail;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
