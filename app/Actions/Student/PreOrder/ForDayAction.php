<?php

namespace App\Actions\Student\PreOrder;

use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use Illuminate\Support\Carbon;

/**
 * Which pre-order applies to a student on a date.
 *
 * A day order wins: active → that order; skipped → nothing that day. Otherwise
 * an active weekly order that includes the weekday applies. Whether it was
 * already collected that day is reported alongside.
 *
 * Read-only, so it returns the value directly.
 */
class ForDayAction
{
    /**
     * @param  array{day?: ?StudentPreOrder, weekly?: ?StudentPreOrder}|null  $loaded  orders already fetched (a schedule resolves many days)
     * @return array{source: ?string, order: ?StudentPreOrder, collection: ?StudentPreOrderCollection}
     *                                                                                                 source: day · weekly · skipped · null
     */
    public function execute(int $accountId, Carbon $date, ?array $loaded = null): array
    {
        $day = $loaded !== null
            ? ($loaded['day'] ?? null)
            : StudentPreOrder::query()
                ->where('account_id', $accountId)
                ->where('type', StudentPreOrder::TYPE_DAY)
                ->whereDate('date', $date->toDateString())
                ->whereIn('status', [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_SKIPPED])
                ->latest('id')
                ->first();

        if ($day?->status === StudentPreOrder::STATUS_SKIPPED) {
            return ['source' => 'skipped', 'order' => $day, 'collection' => null];
        }

        $order = $day;
        if (! $order) {
            $weekly = $loaded !== null ? ($loaded['weekly'] ?? null) : self::weekly($accountId);
            $order = $weekly && $weekly->status === StudentPreOrder::STATUS_ACTIVE && in_array($date->dayOfWeekIso, (array) $weekly->weekdays, true)
                ? $weekly
                : null;
        }

        if (! $order) {
            return ['source' => null, 'order' => null, 'collection' => null];
        }

        return [
            'source' => $order->type,
            'order' => $order,
            'collection' => StudentPreOrderCollection::query()
                ->where('student_pre_order_id', $order->id)
                ->whereDate('date', $date->toDateString())
                ->first(),
        ];
    }

    /** The student's weekly order (active or paused), if any. */
    public static function weekly(int $accountId): ?StudentPreOrder
    {
        return StudentPreOrder::query()
            ->where('account_id', $accountId)
            ->where('type', StudentPreOrder::TYPE_WEEKLY)
            ->whereIn('status', [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_PAUSED])
            ->latest('id')
            ->first();
    }
}
