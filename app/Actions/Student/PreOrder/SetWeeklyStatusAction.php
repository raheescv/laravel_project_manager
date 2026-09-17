<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Account;
use App\Models\Guardian;
use App\Models\StudentPreOrder;
use Exception;

/**
 * Pause, resume or remove the weekly order. Days already given their own order
 * (or skipped) are untouched. The caller has resolved the student through the parent.
 */
class SetWeeklyStatusAction
{
    public function execute(Account $student, Guardian $guardian, string $status): array
    {
        try {
            if (! in_array($status, [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_PAUSED, StudentPreOrder::STATUS_CANCELLED], true)) {
                throw new Exception('Unknown change.', 1);
            }

            $order = ForDayAction::weekly($student->id);
            if (! $order) {
                throw new Exception('There is no weekly order to change.', 1);
            }

            $order->forceFill(['status' => $status, 'guardian_id' => $guardian->id])->save();

            $return['success'] = true;
            $return['message'] = match ($status) {
                StudentPreOrder::STATUS_PAUSED => 'Weekly order paused',
                StudentPreOrder::STATUS_CANCELLED => 'Weekly order removed',
                default => 'Weekly order is on again',
            };
            $return['data'] = $order;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
