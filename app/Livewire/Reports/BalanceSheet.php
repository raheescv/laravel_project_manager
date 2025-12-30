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

    public $retainedEarningAccounts = [];

    // Totals
    public $totalCurrentAssets = 0;

    public $totalFixedAssets = 0;

    public $totalOtherAssets = 0;

    public $totalAssets = 0;

    public $totalCurrentLiabilities = 0;

    public $totalLongTermLiabilities = 0;

    public $totalLiabilities = 0;

    public $totalEquityAccounts = 0;

    public $totalRetainedEarnings = 0;

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

    public function updatedStartDate()
    {
        $this->loadBalanceSheet();
    }

    public function updatedEndDate()
    {
        $this->loadBalanceSheet();
    }

    public function loadBalanceSheet()
    {
        // Reset all arrays and totals
        $this->resetData();

        // Get base query for journal entries within date range
        $periodQuery = JournalEntry::query()
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            });

        // Get opening balance query (entries before start date)
        $openingQuery = JournalEntry::query()
            ->where('date', '<', $this->start_date)
            ->when($this->branch_id, function ($q) {
                return $q->where('branch_id', $this->branch_id);
            });

        $accounts = Account::with('accountCategory')->get();

        foreach ($accounts as $account) {
            // Get period entries
            $periodEntries = (clone $periodQuery)
                ->where('account_id', $account->id)
                ->get();

            // Get opening balance entries
            $openingEntries = (clone $openingQuery)
                ->where('account_id', $account->id)
                ->get();

            // Calculate period totals
            $periodDebit = $periodEntries->sum('debit');
            $periodCredit = $periodEntries->sum('credit');

            // Calculate opening balance totals
            $openingDebit = $openingEntries->sum('debit');
            $openingCredit = $openingEntries->sum('credit');

            // Add account opening balances
            $openingDebit += $account->opening_debit ?? 0;
            $openingCredit += $account->opening_credit ?? 0;

            // Calculate total debits and credits
            $totalDebit = $openingDebit + $periodDebit;
            $totalCredit = $openingCredit + $periodCredit;

            // Calculate balance based on account type
            $balance = $this->calculateAccountBalance($account, $totalDebit, $totalCredit);

            // Only include accounts with non-zero balance
            if (abs($balance) > 0.01) {
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
                // Categorize assets based on account category or default to current
                if ($account->accountCategory && stripos($account->accountCategory->name, 'fixed') !== false) {
                    $this->fixedAssets[] = $accountData;
                    $this->totalFixedAssets += $balance;
                } else {
                    $this->currentAssets[] = $accountData;
                    $this->totalCurrentAssets += $balance;
                }
                break;

            case 'liability':
                // Categorize liabilities based on account category or default to current
                if ($account->accountCategory && stripos($account->accountCategory->name, 'long') !== false) {
                    $this->longTermLiabilities[] = $accountData;
                    $this->totalLongTermLiabilities += $balance;
                } else {
                    $this->currentLiabilities[] = $accountData;
                    $this->totalCurrentLiabilities += $balance;
                }
                break;

            case 'equity':
                $this->ownerEquity[] = $accountData;
                $this->totalEquityAccounts += $balance;
                break;

            case 'income':
                // Income increases retained earnings (credit balance)
                $this->retainedEarningAccounts[] = $accountData;
                $this->totalRetainedEarnings += $balance;
                break;

            case 'expense':
                // Expenses decrease retained earnings (debit balance, shown as negative)
                $this->retainedEarningAccounts[] = $accountData;
                $this->totalRetainedEarnings -= $balance;
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
        // Calculate asset totals
        $this->totalAssets = round($this->totalCurrentAssets + $this->totalFixedAssets + $this->totalOtherAssets, 2);

        // Calculate liability totals
        $this->totalLiabilities = round($this->totalCurrentLiabilities + $this->totalLongTermLiabilities, 2);

        // Calculate equity totals (owner's equity + retained earnings)
        $this->totalEquity = round($this->totalEquityAccounts + $this->totalRetainedEarnings, 2);
    }

    private function calculateAccountBalance($account, $totalDebit, $totalCredit)
    {
        // Calculate balance based on account type and normal balance
        switch ($account->account_type) {
            case 'asset':
            case 'expense':
                // Debit balance normal (debit - credit)
                return round($totalDebit - $totalCredit, 2);

            case 'liability':
            case 'equity':
            case 'income':
                // Credit balance normal (credit - debit)
                return round($totalCredit - $totalDebit, 2);

            default:
                return 0;
        }
    }

    public function render()
    {
        return view('livewire.reports.balance-sheet');
    }
}
