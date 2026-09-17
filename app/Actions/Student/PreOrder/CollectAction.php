<?php

namespace App\Actions\Student\PreOrder;

use App\Models\StudentPreOrderCollection;

/**
 * Mark today's pre-order collected by the sale the till just completed, so the
 * next tap today does not add it again.
 *
 * Quietly does nothing when the order is not the one that applies to this student
 * today (a stale id from the device, a changed order, another student): the sale
 * itself must never fail over pre-order bookkeeping. Idempotent per order per day.
 */
class CollectAction
{
    public function execute(int $preOrderId, int $accountId, int $saleId, ?int $userId = null): ?StudentPreOrderCollection
    {
        $resolved = (new ForDayAction())->execute($accountId, today());
        if (! $resolved['order'] || $resolved['source'] === 'skipped' || (int) $resolved['order']->id !== $preOrderId) {
            return null;
        }

        return StudentPreOrderCollection::firstOrCreate(
            ['student_pre_order_id' => $preOrderId, 'date' => today()->toDateString()],
            ['account_id' => $accountId, 'sale_id' => $saleId, 'created_by' => $userId],
        );
    }
}
