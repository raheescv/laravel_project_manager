<?php

namespace App\Listeners;

use App\Events\PurchaseReturnUpdatedEvent;
use Illuminate\Support\Facades\DB;

class PurchaseReturnUpdateEventListener
{
    public function handle(PurchaseReturnUpdatedEvent $event): void
    {
        $model = $event->purchaseReturn;
        switch ($event->action) {
            case 'payment':
                $model->update(['paid' => $model->payments->sum('amount')]);
                break;
            case 'discount':
                $discount_id = DB::table('accounts')->where('name', 'Discount')->value('id');
                $other_discount = $model->ledgers->where('account_id', $discount_id)->sum('credit');
                $other_discount -= $model->item_discount;
                $model->update(['other_discount' => $other_discount]);
                break;
        }
    }
}
