<?php

namespace App\Listeners;

use App\Events\SaleUpdatedEvent;
use Illuminate\Support\Facades\DB;

class SaleUpdateEventListener
{
    public function handle(SaleUpdatedEvent $event): void
    {
        $sale = $event->sale;
        switch ($event->action) {
            case 'payment':
                $sale->update(['paid' => $sale->payments->sum('amount')]);
                break;
            case 'discount':
                $discount_id = DB::table('accounts')->where('name', 'Discount')->value('id');
                $sale->update(['other_discount' => $sale->ledgers->where('account_id', $discount_id)->sum('debit')]);
                break;
        }
    }
}
