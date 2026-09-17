<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Account;
use App\Models\Guardian;
use App\Models\StudentPreOrder;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Change what happens on one day without an item list:
 * - `skip`: nothing that day, even when the weekly order covers it.
 * - `clear`: drop the day's own order or skip, so the weekly order (if any) applies again.
 *
 * Same cut-off as saving a day. The caller has resolved the student through the parent.
 */
class SetDayAction
{
    public const SKIP = 'skip';

    public const CLEAR = 'clear';

    public function execute(Account $student, Guardian $guardian, string $date, string $action): array
    {
        try {
            $settings = StudentSettings::current();
            if (! $settings->preOrdersOpen()) {
                throw new Exception('The school is not taking pre-orders right now.', 1);
            }
            if (! in_array($action, [self::SKIP, self::CLEAR], true)) {
                throw new Exception('Unknown change.', 1);
            }

            $day = SaveAction::openDate($date, $settings);

            DB::transaction(function () use ($student, $guardian, $day, $action) {
                $order = StudentPreOrder::query()
                    ->where('account_id', $student->id)
                    ->where('type', StudentPreOrder::TYPE_DAY)
                    ->whereDate('date', $day->toDateString())
                    ->whereIn('status', [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_SKIPPED])
                    ->lockForUpdate()
                    ->first();

                if ($action === self::CLEAR) {
                    $order?->forceFill(['status' => StudentPreOrder::STATUS_CANCELLED, 'guardian_id' => $guardian->id])->save();

                    return;
                }

                $order ??= new StudentPreOrder(['account_id' => $student->id, 'type' => StudentPreOrder::TYPE_DAY, 'date' => $day->toDateString()]);
                $order->fill(['status' => StudentPreOrder::STATUS_SKIPPED, 'guardian_id' => $guardian->id, 'note' => null])->save();
                $order->items()->delete();
            });

            $return['success'] = true;
            $return['message'] = $action === self::SKIP ? 'No pre-order that day' : 'Day order removed';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
