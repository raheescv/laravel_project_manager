<?php

namespace App\Livewire\Dashboard\Sale;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Overview extends Component
{
    public $period = 'day';

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

        return $list->map(function ($day) use ($salesData) {
            if (isset($salesData[$day->date])) {
                $day->amount = $salesData[$day->date]->amount;
            }

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

        return $list->map(function ($day) use ($salesData) {
            if (isset($salesData[$day->date])) {
                $day->amount = $salesData[$day->date]->amount;
            }

            return $day;
        });
    }

    private function getMonthlyData()
    {
        // Initialize last 12 months with zero amounts
        $list = collect(range(11, 0, -1))->map(function ($months) {
            $date = now()->subMonths($months);

            return (object) [
                'order_date' => $date->format('Y-m'),
                'date' => $date->format('M Y'),
                'amount' => 0,
            ];
        })->keyBy('order_date');

        // Get actual sales data
        $salesData = Sale::completed()
            ->currentBranch()
            ->lastYear()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as order_date, DATE_FORMAT(date, '%b %Y') as date, SUM(grand_total) as amount")
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y-%m')"), DB::raw("DATE_FORMAT(date, '%b %Y')"))
            ->get()
            ->keyBy('order_date');

        // Merge initialized data with actual sales data
        return $list->map(function ($month, $orderDate) use ($salesData) {
            if (isset($salesData[$orderDate])) {
                $month->amount = $salesData[$orderDate]->amount;
            }

            return (object) [
                'date' => $month->date,
                'amount' => $month->amount,
            ];
        })->values();
    }

    public function render()
    {
        $todaySale = Sale::completed()->currentBranch()->today()->sum('grand_total');
        $todayPayment = SalePayment::completedSale()->currentBranch()->today()->sum('amount');
        $credit = $todaySale - $todayPayment;
        $highestSale = Sale::completed()->currentBranch()->today()->max('grand_total');
        $lowestSale = Sale::completed()->currentBranch()->today()->min('grand_total');

        $paymentData = SalePayment::completedSale()->currentBranch()->today()
            ->join('accounts', 'payment_method_id', '=', 'accounts.id')
            ->select('accounts.name as method')
            ->selectRaw('sum(amount) as amount')
            ->groupBy('payment_method_id')
            ->get()
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
        // dd($this->getMonthlyData()->toArray());
        // Get chart data based on selected period
        $data = match ($this->period) {
            'week' => $this->getWeeklyData(),
            'month' => $this->getMonthlyData(),
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
