<?php

namespace App\Listeners;

use App\Events\PurchaseUpdatedEvent;
use Illuminate\Support\Facades\DB;

class PurchaseUpdateEventListener
{
    public function handle(PurchaseUpdatedEvent $event): void
    {
        $purchase = $event->purchase;
        switch ($event->action) {
            case 'payment':
                $purchase->update(['paid' => $purchase->payments->sum('amount')]);
                break;
            case 'discount':
                $discount_id = DB::table('accounts')->where('name', 'Discount')->value('id');
                $purchase->update(['other_discount' => $purchase->ledgers->where('account_id', $discount_id)->sum('debit')]);
                break;
        }
    }
}
