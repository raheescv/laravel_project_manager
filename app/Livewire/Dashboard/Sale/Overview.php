<?php

namespace App\Livewire\Dashboard\Sale;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\TailoringOrder;
use App\Models\TailoringPayment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Overview extends Component
{
    public $period = 'week';

    public function changePeriod($period)
    {
        $this->period = $period;
    }

    private function getDailyData()
    {
        $list = collect(range(29, 0, -1))->map(function ($days) {
            $date = now()->subDays($days);

            return (object) ['date' => $date->format('d-M'), 'amount' => 0];
        })->keyBy('date');

        $salesData = Sale::completed()
            ->currentBranch()
            ->last30Days()
            ->selectRaw("DATE_FORMAT(date, '%d-%M') as date, SUM(grand_total) as amount")
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $tailoringData = TailoringOrder::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereBetween('order_date', [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')])
            ->selectRaw("DATE_FORMAT(order_date, '%d-%M') as date, SUM(grand_total) as amount")
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return $list->map(function ($day) use ($salesData, $tailoringData) {
            $day->amount = (float) ($salesData[$day->date]->amount ?? 0) + (float) ($tailoringData[$day->date]->amount ?? 0);

            return $day;
        })->values();
    }

    private function getWeeklyData()
    {
        $list = collect(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])->map(function ($day) {
            return (object) ['date' => $day, 'amount' => 0];
        });

        $salesData = Sale::completed()
            ->currentBranch()
            ->last7Days()
            ->selectRaw("DATE_FORMAT(date, '%a') as date, SUM(grand_total) as amount")
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $tailoringData = TailoringOrder::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereBetween('order_date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')])
            ->selectRaw("DATE_FORMAT(order_date, '%a') as date, SUM(grand_total) as amount")
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return $list->map(function ($day) use ($salesData, $tailoringData) {
            $day->amount = (float) ($salesData[$day->date]->amount ?? 0) + (float) ($tailoringData[$day->date]->amount ?? 0);

            return $day;
        });
    }

    private function getMonthlyData()
    {
        $list = collect(range(11, 0, -1))->map(function ($months) {
            $date = now()->subMonths($months);

            return (object) ['order_date' => $date->format('Y-m'), 'date' => $date->format('M Y'), 'amount' => 0];
        })->keyBy('order_date');

        $salesData = Sale::completed()
            ->currentBranch()
            ->lastYear()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as order_date, DATE_FORMAT(date, '%b %Y') as date, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y-%m')"), DB::raw("DATE_FORMAT(date, '%b %Y')"))
            ->get()
            ->keyBy('order_date');

        $tailoringData = TailoringOrder::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereBetween('order_date', [date('Y-m-d', strtotime('-11 month')), date('Y-m-d')])
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') as order_date, DATE_FORMAT(order_date, '%b %Y') as date, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(order_date, '%Y-%m')"), DB::raw("DATE_FORMAT(order_date, '%b %Y')"))
            ->get()
            ->keyBy('order_date');

        return $list->map(function ($month, $orderDate) use ($salesData, $tailoringData) {
            $month->amount = (float) ($salesData[$orderDate]->amount ?? 0) + (float) ($tailoringData[$orderDate]->amount ?? 0);

            return (object) ['date' => $month->date, 'amount' => $month->amount];
        })->values();
    }

    private function getYearlyData()
    {
        $list = collect(range(5, 0, -1))->map(function ($years) {
            $date = now()->subYears($years);

            return (object) ['order_date' => $date->format('Y'), 'date' => $date->format('Y'), 'amount' => 0];
        })->keyBy('order_date');

        $salesData = Sale::completed()
            ->currentBranch()
            ->where('date', '>=', now()->subYears(10)->startOfYear())
            ->selectRaw("DATE_FORMAT(date, '%Y') as order_date, DATE_FORMAT(date, '%Y') as date, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y')"))
            ->get()
            ->keyBy('order_date');

        $tailoringData = TailoringOrder::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->where('order_date', '>=', now()->subYears(10)->startOfYear())
            ->selectRaw("DATE_FORMAT(order_date, '%Y') as order_date, DATE_FORMAT(order_date, '%Y') as date, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(order_date, '%Y')"))
            ->get()
            ->keyBy('order_date');

        return $list->map(function ($year, $orderDate) use ($salesData, $tailoringData) {
            $year->amount = (float) ($salesData[$orderDate]->amount ?? 0) + (float) ($tailoringData[$orderDate]->amount ?? 0);

            return (object) ['date' => $year->date, 'amount' => $year->amount];
        })->values();
    }

    public function render()
    {
        $todaySale = Sale::completed()->currentBranch()->today()->sum('grand_total')
            + TailoringOrder::query()
                ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
                ->whereDate('order_date', date('Y-m-d'))
                ->sum('grand_total');

        $todayPayment = SalePayment::completedSale()->currentBranch()->today()->sum('amount')
            + TailoringPayment::query()
                ->join('tailoring_orders', 'tailoring_payments.tailoring_order_id', '=', 'tailoring_orders.id')
                ->when(session('branch_id'), fn ($q) => $q->where('tailoring_orders.branch_id', session('branch_id')))
                ->whereDate('tailoring_payments.date', date('Y-m-d'))
                ->sum('tailoring_payments.amount');
        $credit = $todaySale - $todayPayment;
        $saleHighest = Sale::completed()->currentBranch()->today()->max('grand_total');
        $tailoringHighest = TailoringOrder::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereDate('order_date', date('Y-m-d'))
            ->max('grand_total');
        $highestSale = max((float) ($saleHighest ?? 0), (float) ($tailoringHighest ?? 0));

        $saleLowest = Sale::completed()->currentBranch()->today()->min('grand_total');
        $tailoringLowest = TailoringOrder::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereDate('order_date', date('Y-m-d'))
            ->min('grand_total');
        $lowestCandidates = array_filter([(float) ($saleLowest ?? 0), (float) ($tailoringLowest ?? 0)], fn ($value) => $value > 0);
        $lowestSale = ! empty($lowestCandidates) ? min($lowestCandidates) : 0;

        $salePaymentData = SalePayment::completedSale()
            ->currentBranch()
            ->today()
            ->join('accounts', 'payment_method_id', '=', 'accounts.id')
            ->select('accounts.name as method')
            ->selectRaw('sum(amount) as amount')
            ->groupBy('payment_method_id')
            ->get();

        $tailoringPaymentData = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_payments.tailoring_order_id', '=', 'tailoring_orders.id')
            ->join('accounts', 'tailoring_payments.payment_method_id', '=', 'accounts.id')
            ->when(session('branch_id'), fn ($q) => $q->where('tailoring_orders.branch_id', session('branch_id')))
            ->whereDate('tailoring_payments.date', date('Y-m-d'))
            ->select('accounts.name as method')
            ->selectRaw('sum(tailoring_payments.amount) as amount')
            ->groupBy('tailoring_payments.payment_method_id', 'accounts.name')
            ->get();

        $paymentData = $salePaymentData
            ->merge($tailoringPaymentData)
            ->groupBy('method')
            ->map(function ($items, $method) {
                return [
                    'method' => $method,
                    'amount' => $items->sum('amount'),
                ];
            })
            ->values()
            ->toArray();

        if ($credit) {
            $paymentData[] = [
                'method' => 'Credit',
                'amount' => $credit,
            ];
        }

        $totalAmount = $todayPayment + $credit;
        foreach ($paymentData as $key => $item) {
            $paymentData[$key]['percentage'] = $totalAmount ? round(($item['amount'] / $totalAmount) * 100, 2) : 0;
        }
        // Get chart data based on selected period
        $data = match ($this->period) {
            'week' => $this->getWeeklyData(),
            'month' => $this->getMonthlyData(),
            'year' => $this->getYearlyData(),
            default => $this->getDailyData(),
        };

        $this->dispatch('chartDataUpdated', $data);

        $data = $data->map(function ($item) {
            return [
                'date' => $item->date,
                'amount' => (float) $item->amount,
            ];
        })->values()->toArray();

        return view('livewire.dashboard.sale.overview', compact('data', 'paymentData', 'highestSale', 'lowestSale', 'todaySale', 'todayPayment'));
    }
}
