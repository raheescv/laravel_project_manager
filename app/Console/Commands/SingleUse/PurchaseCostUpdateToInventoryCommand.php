<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurchaseCostUpdateToInventoryCommand extends Command
{
    protected $signature = 'app:purchase-cost-update-to-inventory';

    protected $description = 'Update inventory cost based on weighted average of unit prices from completed purchases';

    public function handle()
    {
        $this->info('Calculating weighted average costs from completed purchases...');

        // Get weighted average cost per product and branch from completed purchases
        $averageCosts = PurchaseItem::withoutTrashed()
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'completed')->withoutTrashed();
            })
            ->select('purchase_items.product_id', DB::raw('SUM(purchase_items.unit_price * purchase_items.quantity) as total_cost'), DB::raw('SUM(purchase_items.quantity) as total_quantity'))
            ->groupBy('purchase_items.product_id')
            ->havingRaw('SUM(purchase_items.quantity) > 0')
            ->get();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($averageCosts as $costData) {
            $averageCost = round($costData->total_quantity > 0 ? $costData->total_cost / $costData->total_quantity : 0, 2);

            Inventory::withoutGlobalScopes()
                ->where('product_id', $costData->product_id)
                ->update(['cost' => $averageCost]);

            Product::find($costData->product_id)->update(['cost' => $averageCost]);

            $updatedCount++;

            $this->line("Updated inventory for Product ID: {$costData->product_id} - Cost: {$averageCost}");
        }

        $this->info("Completed! Updated: {$updatedCount}, Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }
}
