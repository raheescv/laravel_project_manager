<?php

namespace App\Livewire\Reports;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProfitLoss extends Component
{
    use WithPagination;

    public $start_date;

    public $end_date;

    public $branch_id;

    public $branches = [];

    public $search = '';

    public $period = 'monthly';

    public function mount()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Get branches the user has access to
        $branch_ids = Auth::user()->branches->pluck('branch_id', 'branch_id')->toArray();
        $this->branches = Branch::whereIn('id', $branch_ids)->pluck('name', 'id')->toArray();
        $this->branch_id = '';  // Default to all branches
    }

    public function updatedPeriod($value)
    {
        switch ($value) {
            case 'monthly':
                $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarterly':
                $this->start_date = Carbon::now()->startOfQuarter()->format('Y-m-d');
                $this->end_date = Carbon::now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'yearly':
                $this->start_date = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->end_date = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
            case 'previous_month':
                $this->start_date = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->end_date = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    public function render()
    {
        $query = JournalEntry::query()
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            });

        // Get detailed income breakdown
        $incomeAccounts = Account::where('account_type', 'income')->get();
        $incomeDetails = [];
        $totalIncome = 0;

        // Income types from AccountSeeder:
        // - Sale
        // - Purchase Discount
        foreach ($incomeAccounts as $account) {
            $amount = $query->clone()
                ->where('account_id', $account->id)
                ->sum('credit') - $query->clone()
                ->where('account_id', $account->id)
                ->sum('debit');

            if ($amount != 0) {
                $incomeDetails[$account->name] = $amount;
                $totalIncome += $amount;
            }
        }

        // Get detailed expense breakdown
        $expenseAccounts = Account::where('account_type', 'expense')->get();
        $expenseDetails = [];
        $totalExpenses = 0;

        // Expense types from AccountSeeder:
        // - Sales Returns
        // - Purchase
        // - Discount
        // - Cost of Goods Sold
        // - Freight
        foreach ($expenseAccounts as $account) {
            $amount = $query->clone()
                ->where('account_id', $account->id)
                ->sum('debit') - $query->clone()
                ->where('account_id', $account->id)
                ->sum('credit');

            if ($amount != 0) {
                $expenseDetails[$account->name] = $amount;
                $totalExpenses += $amount;
            }
        }

        // Get gross profit
        $grossProfit = $totalIncome - $totalExpenses;

        // Calculate net profit/loss
        $netProfitLoss = $grossProfit;

        // Prepare chart data
        $incomeChartData = [];
        foreach ($incomeDetails as $name => $amount) {
            if ($amount > 0) {
                $incomeChartData[] = [
                    'name' => $name,
                    'y' => abs($amount),
                ];
            }
        }

        $expenseChartData = [];
        foreach ($expenseDetails as $name => $amount) {
            if ($amount > 0) {
                $expenseChartData[] = [
                    'name' => $name,
                    'y' => abs($amount),
                ];
            }
        }

        // Dispatch event to update charts
        $this->dispatch('refreshCharts', [
            'incomeData' => array_values($incomeChartData), // Convert to indexed array
            'expenseData' => array_values($expenseChartData), // Convert to indexed array
        ]);

        return view('livewire.reports.profit-loss', [
            'incomeDetails' => $incomeDetails,
            'totalIncome' => $totalIncome,
            'expenseDetails' => $expenseDetails,
            'totalExpenses' => $totalExpenses,
            'grossProfit' => $grossProfit,
            'netProfitLoss' => $netProfitLoss,
            'incomeChartData' => json_encode($incomeChartData),
            'expenseChartData' => json_encode($expenseChartData),
            'branches' => $this->branches,
        ]);
    }
}
