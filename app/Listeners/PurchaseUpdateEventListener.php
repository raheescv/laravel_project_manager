<?php

namespace App\Listeners;

use App\Events\PurchaseUpdatedEvent;
use App\Models\Account;

class PurchaseUpdateEventListener
{
    public function handle(PurchaseUpdatedEvent $event): void
    {
        $model = $event->purchase;
        switch ($event->action) {
            case 'payment':
                $model->update(['paid' => $model->payments->sum('amount')]);
                break;
            case 'discount':
                $discount_id = Account::idBySlug('discount');
                $other_discount = $model->ledgers->where('account_id', $discount_id)->sum('credit');
                $other_discount -= $model->item_discount;
                $model->update(['other_discount' => $other_discount]);
                break;
        }
    }
}
