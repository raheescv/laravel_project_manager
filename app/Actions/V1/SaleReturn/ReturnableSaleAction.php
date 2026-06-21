<?php

namespace App\Actions\V1\SaleReturn;

use App\Models\Sale;
use App\Models\SaleReturnItem;

class ReturnableSaleAction
{
    /**
     * Load a completed sale together with each line's remaining returnable
     * quantity (sold quantity minus what has already been returned on
     * non-cancelled sale returns). This is what seeds the "New Return" screen.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $saleId): Sale
    {
        $sale = Sale::query()
            ->with([
                'items.product:id,name,name_arabic,type',
                'items.employee:id,name',
                'account:id,name,mobile',
                'branch:id,name',
            ])
            ->findOrFail($saleId);

        $returned = SaleReturnItem::query()
            ->whereIn('sale_item_id', $sale->items->pluck('id'))
            ->whereHas('saleReturn', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->selectRaw('sale_item_id, COALESCE(SUM(quantity), 0) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        foreach ($sale->items as $item) {
            $alreadyReturned = (float) ($returned[$item->id] ?? 0);
            $item->returned_quantity = round($alreadyReturned, 3);
            $item->returnable_quantity = max(0, round((float) $item->quantity - $alreadyReturned, 3));
        }

        return $sale;
    }
}
