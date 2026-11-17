<?php

namespace App\Actions\Student\Card;

use App\Actions\Student\EnsureAccountsAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\StudentDetail;
use App\Support\Student\StudentSettings;

/**
 * Every card a till can take right now — an active student holding an active
 * card — in the same shape as a single tap's lookup ({@see FindByUidAction}).
 *
 * QLOUD POS keeps this list on the device so a tap is answered from memory
 * instead of a round trip; it still confirms each tap with the server in the
 * background, and the sale itself is guarded by {@see GuardCardPaymentAction}.
 * Blocked cards and inactive students are left out: the till asks the server
 * about any card it does not hold, which is where those get their reason.
 *
 * Read-only: balances come from the ledger in two queries for the whole list.
 */
class ListTappableCardsAction
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $details = StudentDetail::query()
            ->with('account')
            ->whereNotNull('card_uid')
            ->where('card_uid', '<>', '')
            ->where('card_status', StudentDetail::CARD_ACTIVE)
            ->where('status', 'active')
            ->get()
            ->filter(fn (StudentDetail $detail) => $detail->account !== null)
            ->values();

        $balances = (new GetBalanceAction())->many($details->pluck('account_id')->all());
        $limit = StudentSettings::current()->overdraftLimit;
        $cardMethodId = EnsureAccountsAction::cardMethodId();

        return $details
            ->map(fn (StudentDetail $detail) => FindByUidAction::row($detail, $balances[$detail->account_id] ?? 0.0, $limit, $cardMethodId))
            ->all();
    }
}
