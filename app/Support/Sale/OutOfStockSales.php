<?php

namespace App\Support\Sale;

use App\Models\Configuration;
use App\Models\Inventory;
use App\Support\Migration\BulkImport;

class OutOfStockSales
{
    public static function prevented(): bool
    {
        // A bulk migration replays sales in parallel and reconciles stock afterwards, so quantity is
        // deliberately unreliable mid-run. Never block a migrated sale on an out-of-stock check.
        if (BulkImport::enabled()) {
            return false;
        }

        return (Configuration::where('key', 'prevent_out_of_stock_sales')->value('value') ?? 'yes') === 'yes';
    }

    public static function hiddenFromSaleSelection(): bool
    {
        return (Configuration::where('key', 'hide_out_of_stock_sale_items')->value('value') ?? 'yes') === 'yes';
    }

    public static function unavailable(Inventory $inventory, float $requiredQuantity = 0): bool
    {
        if (! self::prevented()) {
            return false;
        }

        return (float) $inventory->quantity <= 0 || (float) $inventory->quantity < $requiredQuantity;
    }

    public static function unavailableForSaleSelection(Inventory $inventory, float $requiredQuantity = 0): bool
    {
        if (! self::hiddenFromSaleSelection()) {
            return false;
        }

        return (float) $inventory->quantity <= 0 || (float) $inventory->quantity < $requiredQuantity;
    }
}
