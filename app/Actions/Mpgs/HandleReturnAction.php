<?php

namespace App\Actions\Mpgs;

use App\Models\QpayTransaction;

/**
 * The parent's browser coming back from the card payment page — after paying,
 * after their last declined retry ($final), or with Cancel / a page timeout
 * ($cancelled).
 *
 * Nothing the browser brings is believed: the order is read back from the gateway
 * (InquireAction) and settled from that. A cancelled payment the gateway holds no
 * money for becomes `cancelled`, which frees the parent to try again at once while
 * the order is still chased for a while in case it gets paid after all.
 */
class HandleReturnAction
{
    public function execute(string $pun, bool $cancelled = false, bool $final = false): ?QpayTransaction
    {
        $transaction = QpayTransaction::where('pun', $pun)
            ->where('type', QpayTransaction::TYPE_PAYMENT)
            ->where('gateway', QpayTransaction::GATEWAY_MPGS)
            ->first();
        if (! $transaction) {
            return null;
        }

        (new InquireAction())->execute($transaction, final: $final && ! $cancelled);

        if ($cancelled && $transaction->refresh()->isPending()) {
            $transaction->forceFill([
                'status' => QpayTransaction::STATUS_CANCELLED,
                'failure_reason' => 'Cancelled on the card payment page.',
            ])->save();
        }

        return $transaction->refresh();
    }
}
