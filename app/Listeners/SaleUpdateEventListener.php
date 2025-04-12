<?php

namespace App\Listeners;

use App\Events\SaleUpdatedEvent;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleUpdateEventListener
{
    public function handle(SaleUpdatedEvent $event): void
    {
        $model = $event->model;
        switch ($event->action) {
            case 'payment':
                $model->update(['paid' => $model->payments->sum('amount')]);
                break;
            case 'discount':
                $discount_id = DB::table('accounts')->where('name', 'Discount')->value('id');
                $other_discount = $model->ledgers
                    ->where('account_id', $discount_id)
                    ->where('remarks', Sale::ADDITIONAL_DISCOUNT_DESCRIPTION)
                    ->sum('debit');
                $model->update(['other_discount' => $other_discount]);
                break;
        }
    }
}
