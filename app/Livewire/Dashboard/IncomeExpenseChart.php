<?php

namespace App\Livewire\Dashboard;

use App\Models\JournalEntry;
use Livewire\Component;

class IncomeExpenseChart extends Component
{
    public function render()
    {
        $filter = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];
        $income = JournalEntry::incomeList($filter)->sum('credit');
        $expense = JournalEntry::expenseList($filter)->sum('debit');

        $chartData = [
            'labels' => ['Income', 'Expense'],
            'datasets' => [[
                'data' => [$income, $expense],
                'backgroundColor' => ['#4CAF50', '#F44336'],
            ]],
        ];

        return view('livewire.dashboard.income-expense-chart', [
            'chartData' => $chartData,
        ]);
    }
}
