<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Account;
use App\Models\Guardian;
use App\Models\StudentPreOrder;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Save a student's pre-order from the parent portal: the order for one day, or
 * the weekly order. Replaces what was there (one day order per date, one weekly
 * order per student) and keeps a weekly order's paused state.
 *
 * The caller has resolved the student through the signed-in parent.
 *
 * @param  array{type: string, date?: ?string, weekdays?: array<int, int>, items: array<int, array{product_id: int, quantity: int}>, note?: ?string}  $data
 */
class SaveAction
{
    private const WEEKDAY_NAMES = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    public function execute(Account $student, Guardian $guardian, array $data): array
    {
        try {
            $settings = StudentSettings::current();
            if (! $settings->preOrdersOpen()) {
                throw new Exception('The school is not taking pre-orders right now.', 1);
            }

            $type = $data['type'] ?? null;
            if (! in_array($type, [StudentPreOrder::TYPE_DAY, StudentPreOrder::TYPE_WEEKLY], true)) {
                throw new Exception('Choose a day or a weekly order.', 1);
            }

            $items = self::items($data['items'] ?? [], $settings);
            $note = filled($data['note'] ?? null) ? mb_substr(trim((string) $data['note']), 0, 200) : null;

            $order = DB::transaction(function () use ($student, $guardian, $type, $data, $items, $note, $settings) {
                if ($type === StudentPreOrder::TYPE_DAY) {
                    $date = self::openDate((string) ($data['date'] ?? ''), $settings);
                    self::assertServed(array_keys($items), [$date->dayOfWeekIso]);
                    $order = StudentPreOrder::query()
                        ->where('account_id', $student->id)
                        ->where('type', StudentPreOrder::TYPE_DAY)
                        ->whereDate('date', $date->toDateString())
                        ->whereIn('status', [StudentPreOrder::STATUS_ACTIVE, StudentPreOrder::STATUS_SKIPPED])
                        ->lockForUpdate()
                        ->first() ?? new StudentPreOrder(['account_id' => $student->id, 'type' => StudentPreOrder::TYPE_DAY, 'date' => $date->toDateString()]);
                    $order->status = StudentPreOrder::STATUS_ACTIVE;
                } else {
                    $weekdays = array_values(array_intersect($settings->schoolDays, array_map('intval', (array) ($data['weekdays'] ?? []))));
                    if (! $weekdays) {
                        throw new Exception('Pick at least one school day for the weekly order.', 1);
                    }
                    self::assertServed(array_keys($items), $weekdays);
                    $order = ForDayAction::weekly($student->id) ?? new StudentPreOrder([
                        'account_id' => $student->id,
                        'type' => StudentPreOrder::TYPE_WEEKLY,
                        'status' => StudentPreOrder::STATUS_ACTIVE,
                    ]);
                    $order->weekdays = $weekdays;
                }

                $order->fill(['guardian_id' => $guardian->id, 'note' => $note])->save();
                self::syncItems($order, $items);

                return $order;
            });

            $return['success'] = true;
            $return['message'] = $type === StudentPreOrder::TYPE_DAY ? 'Pre-order saved' : 'Weekly order saved';
            $return['data'] = $order->load('items');
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * The order's lines, checked against the menu.
     *
     * @return array<int, int> quantity keyed by product id
     */
    public static function items(array $rows, StudentSettings $settings): array
    {
        $items = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $items[$productId] = min(StudentPreOrder::MAX_QUANTITY, ($items[$productId] ?? 0) + $quantity);
        }

        if (! $items) {
            throw new Exception('Add at least one item to the order.', 1);
        }
        if (count($items) > StudentPreOrder::MAX_LINES) {
            throw new Exception('An order can have up to '.StudentPreOrder::MAX_LINES.' different items.', 1);
        }

        $onMenu = MenuAction::query($settings)->whereIn('id', array_keys($items))->pluck('id')->all();
        if (count($onMenu) !== count($items)) {
            throw new Exception('Some items are no longer on the pre-order menu. Please check the order again.', 1);
        }

        return $items;
    }

    /**
     * Every item is on the canteen menu on each of [$weekdays] (Students → Canteen Menu).
     *
     * @param  array<int, int>  $productIds
     * @param  array<int, int>  $weekdays  ISO weekdays
     */
    public static function assertServed(array $productIds, array $weekdays): void
    {
        $menus = MenuAction::menus($productIds);
        foreach ($productIds as $productId) {
            foreach ($weekdays as $weekday) {
                if (! MenuAction::servedOn($menus->get($productId), $weekday)) {
                    $name = $menus->get($productId)->product?->name ?? 'This meal';

                    throw new Exception($name.' is not served on '.self::WEEKDAY_NAMES[$weekday].'s.', 1);
                }
            }
        }
    }

    /** A school day from today up to the booking horizon whose cut-off has not passed. */
    public static function openDate(string $value, StudentSettings $settings): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new Exception('Choose a valid day.', 1);
        }
        $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();

        if (! $settings->isSchoolDay($date)) {
            throw new Exception('The canteen is closed on '.$date->format('l').'s.', 1);
        }
        if ($settings->preOrderLocked($date)) {
            throw new Exception($date->isToday()
                ? 'Orders for today closed at '.$settings->preOrderDeadline($date)->format('g:i A').'.'
                : 'That day has already passed.', 1);
        }
        if ($date->gt(today()->addDays(StudentSettings::PRE_ORDER_DAYS_AHEAD))) {
            throw new Exception('You can pre-order up to '.StudentSettings::PRE_ORDER_DAYS_AHEAD.' days ahead.', 1);
        }

        return $date;
    }

    /** @param  array<int, int>  $items */
    private static function syncItems(StudentPreOrder $order, array $items): void
    {
        $order->items()->whereNotIn('product_id', array_keys($items))->delete();
        foreach ($items as $productId => $quantity) {
            $order->items()->updateOrCreate(['product_id' => $productId], ['quantity' => $quantity, 'tenant_id' => $order->tenant_id]);
        }
    }
}
