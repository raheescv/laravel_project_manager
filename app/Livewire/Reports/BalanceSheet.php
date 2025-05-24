<?php

namespace App\Livewire\Reports;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BalanceSheet extends Component
{
    public $branch_id = '';

    public $period = 'monthly';

    public $start_date;

    public $end_date;

    public $branches = [];

    // Account categories
    public $currentAssets = [];

    public $fixedAssets = [];

    public $otherAssets = [];

    public $currentLiabilities = [];

    public $longTermLiabilities = [];

    public $ownerEquity = [];

    public $retainedEarningAccounts = []; // Changed from single value to array

    // Totals
    public $totalCurrentAssets = 0;

    public $totalFixedAssets = 0;

    public $totalOtherAssets = 0;

    public $totalAssets = 0;

    public $totalCurrentLiabilities = 0;

    public $totalLongTermLiabilities = 0;

    public $totalLiabilities = 0;

    public $totalEquityAccounts = 0;  // Added this for owner's equity total

    public $totalRetainedEarnings = 0; // Added this for retained earnings total

    public $totalEquity = 0;

    public function mount()
    {
        $this->branches = Branch::pluck('name', 'id')->toArray();
        $this->branch_id = Auth::user()->branch_id;
        $this->period = Carbon::now()->format('Y-m');
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadBalanceSheet();
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
        $this->loadBalanceSheet();
    }

    public function updatedBranchId()
    {
        $this->loadBalanceSheet();
    }

    public function loadBalanceSheet()
    {
        // Reset all arrays and totals
        $this->resetData();

        // Query base for journal entries with their journals to get date and branch info
        $query = JournalEntry::query()
            ->join('journals', 'journals.id', '=', 'journal_entries.journal_id')
            ->whereBetween('journals.date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, function ($q) {
                return $q->where('journals.branch_id', $this->branch_id);
            })
            ->select('journal_entries.*');

        $accounts = Account::get();

        foreach ($accounts as $account) {
            // For each account, calculate total credits and debits from journal entries
            $entries = $query->clone()
                ->where('account_id', $account->id)
                ->get();

            // Sum up debits and credits
            $totalDebit = $entries->sum('debit');
            $totalCredit = $entries->sum('credit');

            // Calculate balance based on the account type
            $balance = $this->calculateAccountBalance($account, $totalDebit, $totalCredit);

            if ($balance != 0) {
                $this->categorizeAccount($account, $balance);
            }

        }

        // Calculate totals
        $this->calculateTotals();
    }

    private function resetData()
    {
        // Reset arrays
        $this->currentAssets = [];
        $this->fixedAssets = [];
        $this->otherAssets = [];
        $this->currentLiabilities = [];
        $this->longTermLiabilities = [];
        $this->ownerEquity = [];
        $this->retainedEarningAccounts = [];

        // Reset totals
        $this->totalCurrentAssets = 0;
        $this->totalFixedAssets = 0;
        $this->totalOtherAssets = 0;
        $this->totalAssets = 0;
        $this->totalCurrentLiabilities = 0;
        $this->totalLongTermLiabilities = 0;
        $this->totalLiabilities = 0;
        $this->totalEquityAccounts = 0;
        $this->totalRetainedEarnings = 0;
        $this->totalEquity = 0;
    }

    private function categorizeAccount($account, $balance)
    {
        $accountData = [
            'account' => $account->name,
            'amount' => abs($balance),
        ];

        switch ($account->account_type) {
            case 'asset':
                // For now put all assets as current assets
                $this->currentAssets[] = $accountData;
                $this->totalCurrentAssets += $balance;
                break;
            case 'liability':
                // For now put all liabilities as current liabilities
                $this->currentLiabilities[] = $accountData;
                $this->totalCurrentLiabilities += $balance;
                break;
            case 'expense':
                // Expenses affect retained earnings
                $this->retainedEarningAccounts[] = $accountData;
                $this->totalRetainedEarnings -= $balance; // Subtract expenses
                break;
            case 'income':
                // Income affects retained earnings
                $this->retainedEarningAccounts[] = $accountData;
                $this->totalRetainedEarnings += $balance; // Add income
                break;
            default:
                // Put any unrecognized types in other assets
                $this->otherAssets[] = $accountData;
                $this->totalOtherAssets += $balance;
                break;
        }
    }

    private function calculateTotals()
    {
        $this->totalAssets = $this->totalCurrentAssets + $this->totalFixedAssets + $this->totalOtherAssets;
        $this->totalLiabilities = $this->totalCurrentLiabilities + $this->totalLongTermLiabilities;
        $this->totalEquity = $this->totalEquityAccounts + $this->totalRetainedEarnings;
    }

    private function calculateAccountBalance($account, $totalDebit, $totalCredit)
    {
        // Calculate balance based on account type
        switch ($account->account_type) {
            case 'asset':
            case 'expense':
                return $totalDebit - $totalCredit; // Debit balance normal
            case 'liability':
            case 'equity':
            case 'income':
                return $totalCredit - $totalDebit; // Credit balance
            case 'expense':
                return $totalDebit - $totalCredit; // Debit balance
            default:
                return 0;
        }
    }

    public function render()
    {
        return view('livewire.reports.balance-sheet');
    }
}
