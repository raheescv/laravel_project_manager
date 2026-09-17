<?php

namespace App\Actions\V1\Parent;

use App\Actions\Student\PreOrder\ForDayAction;
use App\Support\Student\StudentSettings;

/**
 * The pre-order line on a child's page: whether the school takes pre-orders,
 * what happens today and whether a weekly order is set up.
 */
class GetPreOrderSummaryAction
{
    public function execute(int $accountId): array
    {
        $settings = StudentSettings::current();
        if (! $settings->preOrdersOpen()) {
            return ['enabled' => false, 'today' => null, 'weekly' => null];
        }

        $today = $settings->isSchoolDay(today()) ? (new ForDayAction())->execute($accountId, today()) : null;
        $weekly = ForDayAction::weekly($accountId);

        return [
            'enabled' => true,
            'today' => $today && $today['source'] ? [
                'source' => $today['source'],
                'items_count' => $today['source'] === 'skipped' ? 0 : (int) $today['order']->items()->sum('quantity'),
                'collected' => (bool) $today['collection'],
            ] : null,
            'weekly' => $weekly ? [
                'status' => $weekly->status,
                'weekdays' => $weekly->weekdays,
            ] : null,
        ];
    }
}
