<?php

namespace App\Actions\Student\Card;

use App\Models\StudentDetail;
use Exception;

/**
 * Freeze a student's card: QLOUD POS refuses it from the next tap.
 *
 * Both the school and a parent can block. Only the school can unblock or link a
 * replacement (UnblockAction / AssignAction), so a parent who reports a card lost
 * cannot be talked into switching it back on by whoever found it.
 *
 * @param  string  $actorType  'user' (school staff) or 'guardian' (parent portal)
 */
class BlockAction
{
    public function execute(int $accountId, string $actorType, int $actorId, ?string $reason = null): array
    {
        try {
            $detail = StudentDetail::where('account_id', $accountId)->first();
            if (! $detail) {
                throw new Exception("Student not found with the specified ID: $accountId.", 1);
            }
            if (! $detail->card_uid) {
                throw new Exception('No card is linked to this student.', 1);
            }

            if (! $detail->isCardBlocked()) {
                $detail->fill([
                    'card_status' => StudentDetail::CARD_BLOCKED,
                    'card_blocked_at' => now(),
                    'card_blocked_by_type' => $actorType,
                    'card_blocked_by_id' => $actorId,
                    'card_block_reason' => $reason ? mb_substr(trim($reason), 0, 255) : null,
                ])->save();
            }

            $return['success'] = true;
            $return['message'] = 'Card blocked. It will be refused at the canteen.';
            $return['data'] = $detail;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
