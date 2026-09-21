<?php

namespace App\Actions\V1\Parent;

use App\Actions\Parent\FindStudentAction;
use App\Actions\Parent\ListStudentsAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Guardian;
use App\Support\Payment\MpgsSettings;
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

        $methods = $settings->portalLink() !== null ? self::methods() : [];

        return array_merge(ListStudentsAction::summary($student, (new GetBalanceAction())->execute($student->id), $settings->overdraftLimit), [
            'card_blocked_at' => $detail?->isCardBlocked() ? $detail->card_blocked_at?->toIso8601String() : null,
            'overdraft_limit' => $settings->overdraftLimit,
            'topup' => [
                // At least one card type is set up, and there is a portal to send the parent back to.
                'enabled' => $methods !== [],
                // The card types the parent can choose between, in the order to show them.
                'methods' => $methods,
                'min' => $settings->topupMin,
                'max' => $settings->topupMax,
                'suggestions' => array_values(array_filter(self::SUGGESTED_TOPUPS, fn ($value) => $value >= $settings->topupMin && $value <= $settings->topupMax)),
            ],
            'pre_orders' => (new GetPreOrderSummaryAction())->execute($student->id),
        ]);
    }

    /**
     * Debit (QPay) and credit (Mastercard Gateway) — each only when the school has
     * switched it on and filled it in (Settings → Student Cards).
     *
     * @return array<int, array{key: string, label: string, detail: string}>
     */
    public static function methods(): array
    {
        return array_values(array_filter([
            QPaySettings::current()->isReady() ? ['key' => 'debit', 'label' => 'Debit card', 'detail' => 'Qatar debit card · QPay'] : null,
            MpgsSettings::current()->isReady() ? ['key' => 'credit', 'label' => 'Credit card', 'detail' => 'Visa · Mastercard'] : null,
        ]));
    }
}
