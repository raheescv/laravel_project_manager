<?php

namespace App\Actions\Student\PreOrder;

use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pay the pre-orders still waiting for their money, from the student cards.
 *
 * - forDate(): when a day's orders close. Weekly orders are paid here, day by day,
 *   and so is a day order whose bank card payment never arrived. A card that
 *   cannot cover its order leaves that order unpaid; the list shows it as not paid.
 * - forStudent(): right after a bank card top-up lands on a card, the day orders
 *   that parent placed and left waiting for that payment.
 *
 * Each order is paid in its own savepoint, so one short card never undoes another
 * child's sale. Never throws: it reports what it did.
 */
class ChargeDueAction
{
    /** @return array{paid: int, failed: array<int, string>} failed: student name => reason */
    public function forDate(Carbon $date, ?int $userId = null): array
    {
        return $this->charge(ForDayAction::allOn($date), $date, $userId);
    }

    /** @return array{paid: int, failed: array<int, string>} */
    public function forStudent(int $accountId, ?int $userId = null): array
    {
        $settings = StudentSettings::current();
        $paid = 0;
        $failed = [];

        $orders = StudentPreOrder::query()
            ->where('account_id', $accountId)
            ->where('type', StudentPreOrder::TYPE_DAY)
            ->where('status', StudentPreOrder::STATUS_ACTIVE)
            ->whereDate('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->get()
            ->reject(fn (StudentPreOrder $order) => $settings->preOrderLocked($order->date));

        foreach ($orders as $order) {
            $result = $this->charge(collect([$order]), $order->date, $userId);
            $paid += $result['paid'];
            $failed += $result['failed'];
        }

        return ['paid' => $paid, 'failed' => $failed];
    }

    /**
     * @param  iterable<StudentPreOrder>  $orders
     * @return array{paid: int, failed: array<int, string>}
     */
    private function charge(iterable $orders, Carbon $date, ?int $userId): array
    {
        $paid = 0;
        $failed = [];

        foreach ($orders as $order) {
            if (StudentPreOrderCollection::where('student_pre_order_id', $order->id)->whereDate('date', $date->toDateString())->exists()) {
                continue;
            }

            try {
                DB::transaction(function () use ($order, $date, $userId): void {
                    $response = (new ChargeAction())->execute($order, $date, $userId);
                    if (! $response['success']) {
                        throw new \RuntimeException($response['message']);
                    }
                });
                $paid++;
            } catch (\Throwable $th) {
                $failed[$order->account_id] = $th->getMessage();
                Log::info('Pre-order not paid', ['order' => $order->id, 'date' => $date->toDateString(), 'reason' => $th->getMessage()]);
            }
        }

        return ['paid' => $paid, 'failed' => $failed];
    }
}
