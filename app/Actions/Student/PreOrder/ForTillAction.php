<?php

namespace App\Actions\Student\PreOrder;

use App\Support\Student\StudentSettings;

/**
 * Today's pre-order for a tapped card, for QLOUD POS to put in the cart — or
 * null when pre-orders are off, there is nothing for today, the day is skipped or
 * it was already collected. Each item carries the product in the shape the app's
 * catalogue uses, at today's price; items taken off the menu are left out.
 *
 * Read-only, so it returns the value directly.
 */
class ForTillAction
{
    public function execute(int $accountId): ?array
    {
        $settings = StudentSettings::current();
        if (! $settings->preOrdersEnabled) {
            return null;
        }

        $resolved = (new ForDayAction())->execute($accountId, today());
        $order = $resolved['order'];
        if (! $order || $resolved['source'] === 'skipped' || $resolved['collection']) {
            return null;
        }

        $order->load('items');
        $products = MenuAction::query($settings)
            ->whereIn('id', $order->items->pluck('product_id'))
            ->with(['mainCategory:id,name', 'images:id,product_id,path,method'])
            ->get()
            ->keyBy('id');

        // Items still on the menu and served today (a menu may have changed since the order).
        $menus = MenuAction::menus($products->keys()->all());
        $items = $order->items
            ->filter(fn ($item) => $products->has($item->product_id) && MenuAction::servedOn($menus->get($item->product_id), today()->dayOfWeekIso))
            ->map(fn ($item) => ['quantity' => $item->quantity, 'product' => MenuAction::product($products->get($item->product_id))])
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'id' => $order->id,
            'source' => $resolved['source'],
            'date' => today()->toDateString(),
            'note' => $order->note,
            'items' => $items->all(),
            'total' => round($items->sum(fn ($item) => $item['product']['mrp'] * $item['quantity']), 2),
            // Items the parent ordered that are no longer sold.
            'missing' => $order->items->count() - $items->count(),
        ];
    }
}
