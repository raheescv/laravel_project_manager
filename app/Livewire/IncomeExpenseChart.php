<?php

namespace App\Livewire;

use App\Models\Models\Views\Ledger;
use Livewire\Component;

class IncomeExpenseChart extends Component
{
    public function render()
    {
        $filter = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];
        $income = Ledger::incomeList($filter)->sum('credit');
        $expense = Ledger::expenseList($filter)->sum('debit');

        $chartData = [
            'labels' => ['Income', 'Expense'],
            'datasets' => [[
                'data' => [$income, $expense],
                'backgroundColor' => ['#4CAF50', '#F44336'],
            ]],
        ];

        return view('livewire.income-expense-chart', [
            'chartData' => $chartData,
        ]);
    }
}
