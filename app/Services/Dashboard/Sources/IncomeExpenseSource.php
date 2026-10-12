<?php

namespace App\Services\Dashboard\Sources;

use App\Models\JournalEntry;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Income against expense: twelve months of bars and this month's split.
 * Ported from the Livewire `dashboard.income-expense-*` components.
 */
class IncomeExpenseSource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('income-expense-bar', 'Finance', 'Income vs expense', 'Last 12 months', 'area-chart', 'chart', 8, 6, ['report.income vs expense dashboard bar chart']),
            new WidgetDefinition('income-expense-pie', 'Finance', 'This month', 'Income vs expense', 'pie-chart', 'pie', 4, 4, ['report.income vs expense dashboard pie chart']),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return ['blocks' => [$key === 'income-expense-pie' ? $this->month() : $this->year()]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function year(): array
    {
        $filter = ['from_date' => now()->subMonths(11)->startOfMonth()->toDateString(), 'to_date' => now()->toDateString()];
        $month = DB::raw('DATE_FORMAT(date, "%Y-%m") as month');
        $income = JournalEntry::incomeList($filter)->select($month, DB::raw('SUM(credit) as total'))->groupBy('month')->pluck('total', 'month');
        $expense = JournalEntry::expenseList($filter)->select($month, DB::raw('SUM(debit) as total'))->groupBy('month')->pluck('total', 'month');

        $months = collect(range(11, 0, -1))->map(fn (int $ago): string => now()->subMonths($ago)->format('Y-m'));

        return Blocks::bars(
            $months->map(fn (string $key): string => Carbon::createFromFormat('Y-m', $key)->format('M Y'))->all(),
            [
                Blocks::series('Income', 'c2', $months->map(fn (string $key): float => (float) ($income[$key] ?? 0))->all()),
                Blocks::series('Expense', 'c4', $months->map(fn (string $key): float => (float) ($expense[$key] ?? 0))->all()),
            ],
            'money', 190, legend: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function month(): array
    {
        $filter = ['start_date' => now()->startOfMonth(), 'end_date' => now()->endOfMonth()];
        $income = (float) JournalEntry::incomeList($filter)->sum('credit');
        $expense = (float) JournalEntry::expenseList($filter)->sum('debit');

        return Blocks::pie([['name' => 'Income', 'value' => $income, 'tone' => 'c2'], ['name' => 'Expense', 'value' => $expense, 'tone' => 'c4']], 'money');
    }
}
