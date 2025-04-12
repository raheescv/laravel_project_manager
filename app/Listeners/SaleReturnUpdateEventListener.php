<?php

namespace App\Listeners;

use App\Events\SaleReturnUpdatedEvent;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class SaleReturnUpdateEventListener
{
    public function handle(SaleReturnUpdatedEvent $event): void
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
                    ->where('remarks', SaleReturn::ADDITIONAL_DISCOUNT_DESCRIPTION)
                    ->sum('credit');
                $model->update(['other_discount' => $other_discount]);
                break;
        }
    }
}
