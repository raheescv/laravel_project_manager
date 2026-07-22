<?php

namespace App\Actions\V1\Dashboard;

use App\Http\Requests\V1\Dashboard\IndexRequest;
use App\Models\Sale;
use App\Models\SalePayment;

class GetAction
{
    /**
     * Build the admin overview dashboard:
     *  - today's sales snapshot
     *  - today's payment split (how much was collected per payment method)
     *  - business overview (weekly / monthly sales with growth %)
     */
    public function execute(IndexRequest $request): array
    {
        $today = today()->toDateString();
        $branchId = $request->validatedWithDefaults()['branch_id'];

        return [
            'date' => $today,
            'todaySummary' => $this->todaySummary($today, $branchId),
            'paymentSplit' => $this->paymentSplit($today, $branchId),
            'bussinessOverview' => $this->bussinessOverview($branchId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todaySummary(string $today, ?int $branchId): array
    {
        $base = Sale::query()
            ->completed()
            ->whereDate('date', $today)
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value));

        return [
            ['title' => "Today's Sales", 'value' => round((float) (clone $base)->sum('paid'), 2), 'type' => 'currency'],
            ['title' => "Today's Bills", 'value' => (clone $base)->count(), 'type' => 'count'],
        ];
    }

    /**
     * How much was collected today, grouped by payment method (Cash / Card /
     * Bank / …). Highest-collecting method first — this is what an owner
     * reconciles against at day-close.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentSplit(string $today, ?int $branchId): array
    {
        return SalePayment::query()
            ->whereHas('sale', function ($query) use ($branchId) {
                $query->where('status', 'completed')
                    ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value));
            })
            ->where('date', $today)
            ->selectRaw('payment_method_id, SUM(amount) as total')
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'title' => $row->paymentMethod?->name ?? 'Unknown',
                'value' => round((float) $row->total, 2),
                'type' => 'currency',
            ])
            ->all();
    }

    /**
     * Last 7 days vs prior 7 days, last 30 days vs prior 30 days.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bussinessOverview(?int $branchId): array
    {
        $today = today();

        $weekFrom = $today->copy()->subDays(6)->toDateString();
        $weekTo = $today->toDateString();
        $prevWeekFrom = $today->copy()->subDays(13)->toDateString();
        $prevWeekTo = $today->copy()->subDays(7)->toDateString();

        $monthFrom = $today->copy()->subDays(29)->toDateString();
        $monthTo = $today->toDateString();
        $prevMonthFrom = $today->copy()->subDays(59)->toDateString();
        $prevMonthTo = $today->copy()->subDays(30)->toDateString();

        $weekly = $this->salesTotal($weekFrom, $weekTo, $branchId);
        $prevWeekly = $this->salesTotal($prevWeekFrom, $prevWeekTo, $branchId);
        $monthly = $this->salesTotal($monthFrom, $monthTo, $branchId);
        $prevMonthly = $this->salesTotal($prevMonthFrom, $prevMonthTo, $branchId);

        return [
            [
                'title' => 'weekly sales',
                'value' => round($weekly, 2),
                'percentage' => $this->growthPercentage($weekly, $prevWeekly),
            ],
            [
                'title' => 'Monthly sales',
                'value' => round($monthly, 2),
                'percentage' => $this->growthPercentage($monthly, $prevMonthly),
            ],
        ];
    }

    private function salesTotal(string $from, string $to, ?int $branchId): float
    {
        return (float) Sale::query()
            ->completed()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($branchId, fn ($q, $value) => $q->where('branch_id', $value))
            ->sum('paid');
    }

    private function growthPercentage(float $current, float $previous): string
    {
        if ($previous == 0.0) {
            return $current > 0 ? '100%' : '0%';
        }

        return round((($current - $previous) / $previous) * 100).'%';
    }
}
