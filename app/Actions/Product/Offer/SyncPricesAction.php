<?php

namespace App\Actions\Product\Offer;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use Exception;

/**
 * Make the offer's `product_prices` rows match [items]: one offer price per
 * product, carrying the offer's dates and status. Unchanged rows are left
 * alone, products dropped from the list lose their row.
 *
 * Rows are written in batches: an offer can hold a whole category of
 * thousands of products, and changing its dates touches every row. Changed
 * rows that share a price are updated together.
 *
 * Throws on failure; the caller's transaction rolls everything back.
 */
class SyncPricesAction
{
    /**
     * @param  array<int, array{product_id: int|string, amount: float|int|string}>  $items
     */
    public function execute(ProductOffer $offer, array $items): void
    {
        $productIds = collect($items)->pluck('product_id')->map(fn ($id): int => (int) $id)->unique()->values();
        if ($productIds->isEmpty()) {
            throw new Exception('Add at least one product to the offer.', 1);
        }
        if ($productIds->count() !== count($items)) {
            throw new Exception('A product can only appear once in an offer.', 1);
        }

        $knownCount = Product::query()->whereIn('id', $productIds)->where('type', $offer->type)->count();
        if ($knownCount !== $productIds->count()) {
            throw new Exception($offer->type === 'service'
                ? 'Some services in this offer no longer exist.'
                : 'Some products in this offer no longer exist.', 1);
        }

        $existing = $offer->prices()->get()->keyBy('product_id');
        $startDate = $offer->start_date->toDateString();
        $endDate = $offer->end_date->toDateString();
        $now = now();
        $inserts = [];
        $updates = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $data = [
                'product_id' => $productId,
                'product_offer_id' => $offer->id,
                'price_type' => 'offer',
                'amount' => round((float) $item['amount'], 3),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $offer->status,
            ];

            $price = $existing->pull($productId);
            if (! $price) {
                $inserts[] = $data + ['created_at' => $now, 'updated_at' => $now];

                continue;
            }
            if ($this->isUnchanged($price->toArray(), $data)) {
                continue;
            }

            $updates[(string) $data['amount']][] = $price->id;
        }

        foreach ($updates as $amount => $ids) {
            foreach (array_chunk($ids, 500) as $chunk) {
                ProductPrice::query()->whereIn('id', $chunk)->update([
                    'amount' => (float) $amount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $offer->status,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            ProductPrice::query()->insert($chunk);
        }

        if ($existing->isNotEmpty()) {
            ProductPrice::query()->whereIn('id', $existing->pluck('id'))->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $next
     */
    private function isUnchanged(array $current, array $next): bool
    {
        return abs((float) $current['amount'] - $next['amount']) < 0.0005
            && substr((string) $current['start_date'], 0, 10) === $next['start_date']
            && substr((string) $current['end_date'], 0, 10) === $next['end_date']
            && $current['status'] === $next['status'];
    }
}
