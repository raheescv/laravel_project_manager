<?php

namespace App\Actions\Student\Card;

use App\Models\StudentDetail;
use Exception;

/** Switch a blocked card back on (school staff only; see BlockAction). */
class UnblockAction
{
    public function execute(int $accountId, int $userId): array
    {
        try {
            $detail = StudentDetail::where('account_id', $accountId)->first();
            if (! $detail) {
                throw new Exception("Student not found with the specified ID: $accountId.", 1);
            }

            $detail->fill([
                'card_status' => StudentDetail::CARD_ACTIVE,
                'card_blocked_at' => null,
                'card_blocked_by_type' => null,
                'card_blocked_by_id' => null,
                'card_block_reason' => null,
                'updated_by' => $userId,
            ])->save();

            $return['success'] = true;
            $return['message'] = 'Card unblocked';
            $return['data'] = $detail;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
