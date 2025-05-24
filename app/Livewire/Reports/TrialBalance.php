<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Livewire\Component;

class TrialBalance extends Component
{
    public $branch_id = '';

    public $period = 'monthly';

    public $start_date;

    public $end_date;

    public $branches;

    public $debitAccounts = [];

    public $creditAccounts = [];

    public $totalDebit = 0;

    public $totalCredit = 0;

    // Section totals
    public $assets = [];

    public $liabilities = [];

    public $equity = [];

    public $income = [];

    public $expenses = [];

    public $totalAssets = 0;

    public $totalLiabilities = 0;

    public $netBalance = 0;

    // Section totals for debit/credit
    public $totalAssetsDebit = 0;

    public $totalAssetsCredit = 0;

    public $totalLiabilitiesDebit = 0;

    public $totalLiabilitiesCredit = 0;

    public $totalEquityDebit = 0;

    public $totalEquityCredit = 0;

    public $totalIncomeDebit = 0;

    public $totalIncomeCredit = 0;

    public $totalExpensesDebit = 0;

    public $totalExpensesCredit = 0;

    public function mount()
    {
        $this->branches = Branch::pluck('name', 'id')->toArray();

        // Set default dates based on current month
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->loadTrialBalance();
    }

    public function updatedPeriod()
    {
        switch ($this->period) {
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

        $this->loadTrialBalance();
    }

    public function updatedStartDate()
    {
        $this->loadTrialBalance();
    }

    public function updatedEndDate()
    {
        $this->loadTrialBalance();
    }

    public function updatedBranchId()
    {
        $this->loadTrialBalance();
    }

    protected function loadTrialBalance()
    {
        $query = JournalEntry::query()
            ->selectRaw('
                account_id,
                accounts.name as account_name,
                accounts.account_type,
                COALESCE(SUM(journal_entries.debit), 0) as total_debit,
                COALESCE(SUM(journal_entries.credit), 0) as total_credit
            ')
            ->join('journals', 'journals.id', '=', 'journal_id')
            ->join('accounts', 'accounts.id', '=', 'account_id')
            ->whereBetween('journals.date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, function ($query) {
                return $query->where('journals.branch_id', $this->branch_id);
            })
            ->groupBy('account_id', 'accounts.name', 'accounts.account_type');

        $accounts = $query->get();

        // Reset arrays and totals
        $this->assets = [];
        $this->liabilities = [];
        $this->equity = [];
        $this->income = [];
        $this->expenses = [];

        $this->totalAssetsDebit = 0;
        $this->totalAssetsCredit = 0;
        $this->totalLiabilitiesDebit = 0;
        $this->totalLiabilitiesCredit = 0;
        $this->totalEquityDebit = 0;
        $this->totalEquityCredit = 0;
        $this->totalIncomeDebit = 0;
        $this->totalIncomeCredit = 0;
        $this->totalExpensesDebit = 0;
        $this->totalExpensesCredit = 0;

        // Process and categorize accounts
        foreach ($accounts as $account) {
            $debit = round((float) ($account->total_debit ?? 0), 2);
            $credit = round((float) ($account->total_credit ?? 0), 2);

            $accountData = (object) [
                'code' => $account->account_code,
                'name' => $account->account_name,
                'debit' => $debit,
                'credit' => $credit,
            ];

            switch ($account->account_type) {
                case 'asset':
                    $this->assets[] = $accountData;
                    $this->totalAssetsDebit = bcadd($this->totalAssetsDebit, $debit, 2);
                    $this->totalAssetsCredit = bcadd($this->totalAssetsCredit, $credit, 2);
                    break;
                case 'liability':
                    $this->liabilities[] = $accountData;
                    $this->totalLiabilitiesDebit = bcadd($this->totalLiabilitiesDebit, $debit, 2);
                    $this->totalLiabilitiesCredit = bcadd($this->totalLiabilitiesCredit, $credit, 2);
                    break;
                case 'equity':
                    $this->equity[] = $accountData;
                    $this->totalEquityDebit = bcadd($this->totalEquityDebit, $debit, 2);
                    $this->totalEquityCredit = bcadd($this->totalEquityCredit, $credit, 2);
                    break;
                case 'income':
                    $this->income[] = $accountData;
                    $this->totalIncomeDebit = bcadd($this->totalIncomeDebit, $debit, 2);
                    $this->totalIncomeCredit = bcadd($this->totalIncomeCredit, $credit, 2);
                    break;
                case 'expense':
                    $this->expenses[] = $accountData;
                    $this->totalExpensesDebit = bcadd($this->totalExpensesDebit, $debit, 2);
                    $this->totalExpensesCredit = bcadd($this->totalExpensesCredit, $credit, 2);
                    break;
            }
        }

        // Calculate total assets (assets have a debit balance normally)
        $this->totalAssets = bcsub($this->totalAssetsDebit, $this->totalAssetsCredit, 2);

        // Calculate total liabilities (liabilities have a credit balance normally)
        $this->totalLiabilities = bcsub($this->totalLiabilitiesCredit, $this->totalLiabilitiesDebit, 2);

        // Calculate net balance
        $this->netBalance = bcsub($this->totalAssets, $this->totalLiabilities, 2);

        // Calculate grand totals
        $this->totalDebit = array_sum([
            $this->totalAssetsDebit,
            $this->totalLiabilitiesDebit,
            $this->totalEquityDebit,
            $this->totalIncomeDebit,
            $this->totalExpensesDebit,
        ]);

        $this->totalCredit = array_sum([
            $this->totalAssetsCredit,
            $this->totalLiabilitiesCredit,
            $this->totalEquityCredit,
            $this->totalIncomeCredit,
            $this->totalExpensesCredit,
        ]);

        // Ensure proper decimal precision
        $this->totalDebit = round($this->totalDebit, 2);
        $this->totalCredit = round($this->totalCredit, 2);
    }

    public function render()
    {
        return view('livewire.reports.trial-balance');
    }
}
