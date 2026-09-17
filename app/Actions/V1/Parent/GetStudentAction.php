<?php

namespace App\Actions\V1\Parent;

use App\Actions\Parent\FindStudentAction;
use App\Actions\Parent\ListStudentsAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Guardian;
use App\Support\Payment\QPaySettings;
use App\Support\Student\StudentSettings;

/**
 * One child for the portal: the home card's summary, plus when the card was
 * blocked, the overdraft limit and whether (and within what range) it can be
 * topped up online right now.
 */
class GetStudentAction
{
    public const SUGGESTED_TOPUPS = [50, 100, 200, 500];

    public function execute(Guardian $guardian, int|string $accountId): array
    {
        $student = (new FindStudentAction())->execute($guardian, $accountId);
        $settings = StudentSettings::current();
        $detail = $student->studentDetail;

        return array_merge(ListStudentsAction::summary($student, (new GetBalanceAction())->execute($student->id), $settings->overdraftLimit), [
            'card_blocked_at' => $detail?->isCardBlocked() ? $detail->card_blocked_at?->toIso8601String() : null,
            'overdraft_limit' => $settings->overdraftLimit,
            'topup' => [
                // Online payment is set up, and QPay has a portal to send the parent back to.
                'enabled' => QPaySettings::current()->isReady() && $settings->portalLink() !== null,
                'min' => $settings->topupMin,
                'max' => $settings->topupMax,
                'suggestions' => array_values(array_filter(self::SUGGESTED_TOPUPS, fn ($value) => $value >= $settings->topupMin && $value <= $settings->topupMax)),
            ],
            'pre_orders' => (new GetPreOrderSummaryAction())->execute($student->id),
        ]);
    }
}
