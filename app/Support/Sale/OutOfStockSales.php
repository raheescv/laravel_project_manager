<?php

namespace App\Support\Sale;

use App\Models\Configuration;
use App\Models\Inventory;

class OutOfStockSales
{
    public static function prevented(): bool
    {
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
        return (float) $inventory->quantity <= 0 || (float) $inventory->quantity < $requiredQuantity;
    }
}
