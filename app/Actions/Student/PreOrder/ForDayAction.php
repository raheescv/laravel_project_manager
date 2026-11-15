<?php

namespace App\Actions\Student\PreOrder;

use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

    /**
     * The order that applies on [$date] for every student who has one, by the same
     * rules as execute(): a day order wins, a skipped day drops the weekly order, an
     * active weekly order covering the weekday applies.
     *
     * @return EloquentCollection<int, StudentPreOrder>
     */
    public static function allOn(Carbon $date): EloquentCollection
    {
        $dayOrders = StudentPreOrder::query()
            ->where('type', StudentPreOrder::TYPE_DAY)
            ->whereDate('date', $date->toDateString())
            ->whereIn('status', [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_SKIPPED])
            ->latest('id')
            ->get()
            ->unique('account_id')
            ->keyBy('account_id');

        $weeklyOrders = StudentPreOrder::query()
            ->where('type', StudentPreOrder::TYPE_WEEKLY)
            ->where('status', StudentPreOrder::STATUS_ACTIVE)
            ->latest('id')
            ->get()
            ->unique('account_id')
            ->filter(fn (StudentPreOrder $order) => in_array($date->dayOfWeekIso, (array) $order->weekdays, true))
            ->keyBy('account_id');

        return new EloquentCollection($dayOrders->keys()->merge($weeklyOrders->keys())->unique()
            ->map(function (int $accountId) use ($dayOrders, $weeklyOrders) {
                $day = $dayOrders->get($accountId);

                return $day?->status === StudentPreOrder::STATUS_SKIPPED ? null : ($day ?? $weeklyOrders->get($accountId));
            })
            ->filter()
            ->values()
            ->all());
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
