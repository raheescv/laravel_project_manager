<?php

namespace App\Actions\V1\Parent;

use App\Models\Guardian;
use App\Models\QpayTransaction;

/**
 * A top-up the parent started, by its payment reference. Only payments for this
 * parent's own children; anyone else's is a 404.
 */
class GetTopupAction
{
    public function execute(Guardian $guardian, string $pun): QpayTransaction
    {
        return QpayTransaction::with('account:id,name')
            ->where('pun', $pun)
            ->where('type', QpayTransaction::TYPE_PAYMENT)
            ->whereIn('account_id', $guardian->students()->pluck('accounts.id'))
            ->firstOrFail();
    }
}
