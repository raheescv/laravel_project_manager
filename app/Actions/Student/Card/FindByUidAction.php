<?php

namespace App\Actions\Student\Card;

use App\Actions\Student\EnsureAccountsAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\StudentDetail;
use App\Support\Student\StudentSettings;

/**
 * Who a tapped card belongs to and what it can spend — what QLOUD POS shows the
 * cashier (with the student's photo, to check the card is in the right hands).
 *
 * Read-only: returns the summary, or null when no student holds the card. A
 * blocked card is still returned (with its status) so the till can say why.
 */
class FindByUidAction
{
    public function execute(?string $uid): ?array
    {
        $uid = StudentDetail::normalizeCardUid($uid);
        if (! $uid) {
            return null;
        }

        $detail = StudentDetail::with('account')->where('card_uid', $uid)->first();
        if (! $detail || ! $detail->account) {
            return null;
        }

        return self::summary($detail);
    }

    public static function summary(StudentDetail $detail): array
    {
        return self::row(
            $detail,
            (new GetBalanceAction())->execute($detail->account->id),
            StudentSettings::current()->overdraftLimit,
            EnsureAccountsAction::cardMethodId(),
        );
    }

    /**
     * The summary from figures already in hand, so a list of cards
     * ({@see ListTappableCardsAction}) reads the ledger and settings once.
     */
    public static function row(StudentDetail $detail, float $balance, float $limit, ?int $cardMethodId): array
    {
        $account = $detail->account;

        return [
            'account_id' => $account->id,
            'name' => $account->name,
            'image_url' => $account->image ? $account->image_url : null,
            'admission_no' => $detail->admission_no,
            'grade' => $detail->grade,
            'section' => $detail->section,
            'status' => $detail->status,
            'card_uid' => $detail->card_uid,
            'card_status' => $detail->card_status,
            'balance' => $balance,
            'overdraft_limit' => $limit,
            'available' => max(0, round($balance + $limit, 2)),
            // The Student Card payment method, so the till can put the card into a split payment.
            'card_method_id' => $cardMethodId,
            // Always null: pre-orders are paid as their own sale in the parent portal, so a tap
            // never puts one in the cart. Kept for app builds that still read the key.
            'pre_order' => null,
        ];
    }
}
