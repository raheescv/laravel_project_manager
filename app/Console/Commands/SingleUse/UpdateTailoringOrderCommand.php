<?php

namespace App\Console\Commands\SingleUse;

use App\Models\TailoringOrder;
use Illuminate\Console\Command;

class UpdateTailoringOrderCommand extends Command
{
    protected $signature = 'app:update-tailoring-order-command';

    protected $description = 'calculate total based on items values';

    public function handle()
    {
        $tailoringOrders = TailoringOrder::get();
        foreach ($tailoringOrders as $tailoringOrder) {
            $tailoringOrder->calculateTotals();
            $tailoringOrder->save();
            $this->info('Tailoring order '.$tailoringOrder->id.' updated');
        }
    }
}
