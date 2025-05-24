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
                SUM(debit) as total_debit,
                SUM(credit) as total_credit
            ')
            ->join('journals', 'journals.id', '=', 'journal_id')
            ->join('accounts', 'accounts.id', '=', 'account_id')
            ->whereBetween('journals.date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, function ($query) {
                return $query->where('branch_id', $this->branch_id);
            })
            ->groupBy('account_id', 'accounts.name', 'accounts.account_type');

        $accounts = $query->get();

        // Initialize arrays
        $debitAccounts = [];
        $creditAccounts = [];

        // Sort accounts by type and their normal balance
        foreach ($accounts as $account) {
            $balance = [
                'account' => $account->account_name,
                'debit' => $account->total_debit,
                'credit' => $account->total_credit,
            ];

            // Assets and Expenses normally have debit balances
            if (in_array($account->account_type, ['asset', 'expense'])) {
                $netBalance = $account->total_debit - $account->total_credit;
                if ($netBalance != 0) {
                    $balance['debit'] = max(0, $netBalance);
                    $balance['credit'] = max(0, -$netBalance);
                    $debitAccounts[] = $balance;
                }
            }
            // Liabilities, Income, and Capital normally have credit balances
            elseif (in_array($account->account_type, ['liability', 'income'])) {
                $netBalance = $account->total_credit - $account->total_debit;
                if ($netBalance != 0) {
                    $balance['debit'] = max(0, -$netBalance);
                    $balance['credit'] = max(0, $netBalance);
                    $creditAccounts[] = $balance;
                }
            }
        }

        // Sort accounts by name
        $this->debitAccounts = collect($debitAccounts)->sortBy('account')->values()->all();
        $this->creditAccounts = collect($creditAccounts)->sortBy('account')->values()->all();

        $this->totalDebit = collect($this->debitAccounts)->sum('debit');
        $this->totalCredit = collect($this->creditAccounts)->sum('credit');
    }

    public function render()
    {
        return view('livewire.reports.trial-balance');
    }
}
