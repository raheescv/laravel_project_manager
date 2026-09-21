<?php

namespace App\Actions\V1\Parent;

use App\Actions\QPay\InquireAction;
use App\Actions\QPay\StartPaymentAction;
use App\Exceptions\ParentPortalException;
use App\Models\Guardian;
use App\Models\QpayTransaction;

/**
 * The parent's "Cancel this payment": they opened QPay's page, left without paying,
 * and the unfinished payment now stands in the way of a new top-up.
 *
 * Nothing is cancelled on the parent's word. The block is QPay certification — a
 * parent who closed QPay's page must not pay twice — and from here "I never paid"
 * looks exactly like "I paid and the result was lost". So QPay is asked, as the Pay
 * button does after the same 20 minutes, and the payment closes only when QPay says
 * it never received it. Inside those 20 minutes the parent may still be on QPay's
 * page, so it is refused, as the office's Release is. If QPay says the money WAS
 * taken, the card is credited instead — the parent gets the answer either way.
 *
 * A payment that already has an outcome is handed back as it is: the scheduled
 * inquiry may have settled it while the page was open.
 */
class CancelTopupAction
{
    public function execute(Guardian $guardian, string $pun): QpayTransaction
    {
        $transaction = (new GetTopupAction())->execute($guardian, $pun);

        if (! $transaction->isPending()) {
            return $transaction;
        }

        // Only a QPay payment holds the card; a credit card one never blocks a new top-up.
        if ($transaction->isCreditCard()) {
            throw new ParentPortalException('A credit card payment does not need cancelling — you can top up again straight away.');
        }

        $from = StartPaymentAction::inquirableAt($transaction);
        if (now()->lt($from)) {
            throw new ParentPortalException(
                'This payment was started less than '.StartPaymentAction::BROKEN_AFTER_MINUTES.' minutes ago and may still be going through on QPay. You can cancel it after '.$from->format('h:i A').'.',
                ['retry_at' => $from->toIso8601String()],
            );
        }

        (new InquireAction())->execute($transaction);
        $transaction->refresh();

        if ($transaction->isPending()) {
            throw new ParentPortalException(
                "QPay hasn't told us what happened to this payment yet, so it can't be cancelled. Please try again in a few minutes. If it still won't cancel, the school office can release it for you.",
                ['retry_at' => now()->addMinutes(StartPaymentAction::RETRY_AFTER_INQUIRY_MINUTES)->toIso8601String()],
            );
        }

        // Say on the office's Top-ups tab why it closed, not only QPay's words.
        if ($transaction->status === QpayTransaction::STATUS_FAILED) {
            $transaction->forceFill([
                'failure_reason' => 'Cancelled by the parent in the portal; QPay confirmed it never received the payment'.($transaction->failure_reason ? ' ('.$transaction->failure_reason.')' : '').'.',
            ])->save();
        }

        return $transaction->load('account:id,name');
    }
}
