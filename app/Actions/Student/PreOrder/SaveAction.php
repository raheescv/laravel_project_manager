<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Account;
use App\Models\Guardian;
use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Save a student's pre-order from the parent portal: the order for one day, or
 * the weekly order. Replaces what was there (one day order per date, one weekly
 * order per student) and keeps a weekly order's paused state.
 *
 * A day order is paid now: `pay: card` (default) takes it from the student card
 * as a completed sale on that day (ChargeAction), all or nothing, so a short card
 * saves nothing; `pay: bank` saves it waiting for the bank card top-up the portal
 * opens next, which pays it the moment it lands (ChargeDueAction::forStudent);
 * `pay: later` only saves the cart, to be paid by a later save or from the card
 * when the day's orders close (ChargeDueAction::forDate).
 * Changing a paid day refunds the old sale to the card first; saving it unchanged
 * keeps the sale. A weekly order is paid day by day when each day's orders close.
 *
 * The caller has resolved the student through the signed-in parent.
 *
 * @param  array{type: string, date?: ?string, weekdays?: array<int, int>, items: array<int, array{product_id: int, quantity: int}>, note?: ?string, pay?: ?string}  $data
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

                $unchanged = $order->exists && $order->note === $note
                    && $order->items()->pluck('quantity', 'product_id')->map(fn ($quantity) => (int) $quantity)->sortKeys()->all() === collect($items)->sortKeys()->all();

                $order->fill(['guardian_id' => $guardian->id, 'note' => $note])->save();
                self::syncItems($order, $items);

                if ($type === StudentPreOrder::TYPE_DAY) {
                    self::settleDay($order, $unchanged, in_array($data['pay'] ?? 'card', ['bank', 'later'], true));
                }

                return $order;
            });

            $paid = $type === StudentPreOrder::TYPE_DAY && StudentPreOrderCollection::where('student_pre_order_id', $order->id)->whereDate('date', $order->date)->exists();
            $return['success'] = true;
            $return['message'] = match (true) {
                $type === StudentPreOrder::TYPE_WEEKLY => 'Weekly order saved',
                $paid => 'Pre-order paid',
                ($data['pay'] ?? null) === 'later' => 'Pre-order saved. It is not paid yet.',
                default => 'Pre-order saved. Pay by bank card to confirm it.',
            };
            $return['data'] = $order->load('items')->setAttribute('paid', $paid);
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
                    $name = $menus->get($productId)->product->name ?? 'This meal';

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
        if ($date->lt(today())) {
            throw new Exception('That day has already passed.', 1);
        }
        if ($settings->preOrderLocked($date)) {
            $day = match (true) {
                $date->isToday() => 'today',
                $date->isTomorrow() => 'tomorrow',
                default => $date->format('l j M'),
            };

            throw new Exception('Orders for '.$day.' closed at '.$settings->preOrderCutoffLabel().'.', 1);
        }
        if ($date->gt(today()->addDays(StudentSettings::PRE_ORDER_DAYS_AHEAD))) {
            throw new Exception('You can pre-order up to '.StudentSettings::PRE_ORDER_DAYS_AHEAD.' days ahead.', 1);
        }

        return $date;
    }

    /**
     * Pay the day: an unchanged, paid order keeps its sale; otherwise a sale already
     * taken is refunded and, unless the parent pays later (bank card next, or just
     * saving the cart), the new order is taken from the card. Throws when the card
     * cannot cover it.
     */
    private static function settleDay(StudentPreOrder $order, bool $unchanged, bool $payLater): void
    {
        $previous = StudentPreOrderCollection::where('student_pre_order_id', $order->id)->whereDate('date', $order->date)->first();
        if ($previous && $unchanged) {
            return;
        }
        if ($previous) {
            $response = (new RefundAction())->execute($previous);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
        if ($payLater) {
            return;
        }

        $response = (new ChargeAction())->execute($order->unsetRelation('items'), $order->date);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
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
