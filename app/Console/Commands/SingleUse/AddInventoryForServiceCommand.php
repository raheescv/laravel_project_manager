<?php

namespace App\Console\Commands\SingleUse;

use Illuminate\Console\Command;

use App\Models\Inventory;
use App\Models\Product;


class AddInventoryForServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-inventory-for-service-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = 1;
        $products = Product::service()->get();
        foreach ($products as $product) {
            $exists = Inventory::where('product_id', $product->id)->exists();
            if (!$exists) {
                Inventory::selfCreateByProduct($product, $userId, $quantity = 0);
            }
        }
    }
}
