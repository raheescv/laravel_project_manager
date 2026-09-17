<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Product;
use App\Models\StudentPreOrder;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A student's pre-orders as the parent portal shows them: the weekly order and
 * what will happen on each of the next school days.
 *
 * Read-only, so it returns the value directly.
 */
class ScheduleAction
{
    public const DAYS = 10;

    public function execute(int $accountId, int $days = self::DAYS): array
    {
        $settings = StudentSettings::current();
        $weekly = ForDayAction::weekly($accountId);

        // From today (whose order may be locked but still waiting at the till).
        $dates = [];
        for ($date = today(); count($dates) < $days && $date->lte(today()->addDays(StudentSettings::PRE_ORDER_DAYS_AHEAD)); $date = $date->copy()->addDay()) {
            if ($settings->isSchoolDay($date)) {
                $dates[] = $date->copy();
            }
        }

        $dayOrders = StudentPreOrder::query()
            ->where('account_id', $accountId)
            ->where('type', StudentPreOrder::TYPE_DAY)
            ->whereIn('status', [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_SKIPPED])
            ->whereBetween('date', [today()->toDateString(), today()->addDays(StudentSettings::PRE_ORDER_DAYS_AHEAD)->toDateString()])
            ->with('items')
            ->latest('id')
            ->get()
            ->unique(fn (StudentPreOrder $order) => $order->date->toDateString())
            ->keyBy(fn (StudentPreOrder $order) => $order->date->toDateString());

        $weekly?->load('items');
        $products = $this->products(collect([$weekly])->filter()->merge($dayOrders->values()));

        $resolver = new ForDayAction();

        return [
            'enabled' => $settings->preOrdersOpen(),
            'cutoff' => $settings->preOrderCutoff,
            'school_days' => $settings->schoolDays,
            'max_quantity' => StudentPreOrder::MAX_QUANTITY,
            'weekly' => $weekly ? $this->order($weekly, $products) : null,
            'days' => array_map(function (Carbon $date) use ($resolver, $accountId, $dayOrders, $weekly, $products, $settings) {
                $resolved = $resolver->execute($accountId, $date, ['day' => $dayOrders->get($date->toDateString()), 'weekly' => $weekly]);

                return [
                    'date' => $date->toDateString(),
                    'source' => $resolved['source'],
                    'locked' => $settings->preOrderLocked($date),
                    'deadline' => $settings->preOrderDeadline($date)->toIso8601String(),
                    'collected' => (bool) $resolved['collection'],
                    'order' => $resolved['order'] && $resolved['source'] !== 'skipped' ? $this->order($resolved['order'], $products) : null,
                ];
            }, $dates),
        ];
    }

    /** @param  Collection<int, Product>  $products */
    private function order(StudentPreOrder $order, Collection $products): array
    {
        $items = $order->items->map(function ($item) use ($products) {
            $product = $products->get($item->product_id);

            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'name' => $product?->name ?? 'Item no longer sold',
                'price' => round((float) ($product?->mrp ?? 0), 2),
                'thumbnail' => $product ? MenuAction::product($product)['thumbnail'] : null,
                'available' => (bool) $product,
            ];
        })->values();

        return [
            'id' => $order->id,
            'type' => $order->type,
            'status' => $order->status,
            'weekdays' => $order->weekdays,
            'note' => $order->note,
            'items' => $items->all(),
            'items_count' => (int) $items->sum('quantity'),
            'total' => round($items->sum(fn ($item) => $item['price'] * $item['quantity']), 2),
        ];
    }

    /** Products still on the menu, keyed by id. */
    private function products(Collection $orders): Collection
    {
        $ids = $orders->flatMap(fn (StudentPreOrder $order) => $order->items->pluck('product_id'))->unique()->all();

        return $ids
            ? MenuAction::query(StudentSettings::current())->whereIn('id', $ids)->with('images:id,product_id,path,method')->get()->keyBy('id')
            : collect();
    }
}
