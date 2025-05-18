<?php

namespace App\Livewire\Dashboard;

use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IncomeExpenseBarChart extends Component
{
    public function getChartData()
    {
        $filter = [
            'from_date' => Carbon::now()->subMonths(11)->startOfMonth()->format('Y-m-d'),
            'to_date' => Carbon::now()->format('Y-m-d'),
        ];

        $dates = collect();
        $current = Carbon::parse($filter['from_date']);
        $end = Carbon::parse($filter['to_date']);

        while ($current <= $end) {
            $dates->push($current->format('Y-m'));
            $current->addMonth();
        }

        $income = JournalEntry::incomeList($filter)
            ->select(DB::raw('DATE_FORMAT(date, "%Y-%m") as month'), DB::raw('SUM(debit) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $expense = JournalEntry::expenseList($filter)
            ->select(DB::raw('DATE_FORMAT(date, "%Y-%m") as month'), DB::raw('SUM(debit) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $chartData = $dates->map(function ($month) use ($income, $expense) {
            $date = Carbon::createFromFormat('Y-m', $month);

            return [
                'date' => $date->format('M Y'),
                'income' => $income[$month] ?? 0,
                'expense' => $expense[$month] ?? 0,
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
