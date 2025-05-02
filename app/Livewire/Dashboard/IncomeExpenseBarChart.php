<?php

namespace App\Livewire\Dashboard;

use App\Models\Models\Views\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IncomeExpenseBarChart extends Component
{
    public function getChartData()
    {
        $filter = [
            'from_date' => date('Y-m-d', strtotime('-30 days')),
            'to_date' => date('Y-m-d'),
        ];

        $dates = collect();
        $current = Carbon::parse($filter['from_date']);
        $end = Carbon::parse($filter['to_date']);

        while ($current <= $end) {
            $dates->push($current->format('Y-m-d'));
            $current->addDay();
        }

        $income = Ledger::incomeList($filter)
            ->groupBy('date')
            ->select('date', DB::raw('SUM(credit) as income'))
            ->pluck('income', 'date')
            ->toArray();

        $expense = Ledger::expenseList($filter)
            ->groupBy('date')
            ->select('date', DB::raw('SUM(debit) as expense'))
            ->pluck('expense', 'date')
            ->toArray();

        $chartData = $dates->map(function ($date) use ($income, $expense) {
            return [
                'date' => date('D (d) M', strtotime($date)),
                'income' => $income[$date] ?? 0,
                'expense' => $expense[$date] ?? 0,
            ];
        });

        return [
            'labels' => $chartData->pluck('date'),
            'income' => $chartData->pluck('income'),
            'expense' => $chartData->pluck('expense'),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.income-expense-bar-chart', [
            'chartData' => $this->getChartData(),
        ]);
    }
}
