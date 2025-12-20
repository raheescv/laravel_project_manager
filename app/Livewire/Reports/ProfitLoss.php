<?php

namespace App\Livewire\Reports;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfitLoss extends Component
{
    public $start_date;

    public $end_date;

    public $branch_id;

    public $branches = [];

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

    /**
     * Apply branch filter to query
     */
    protected function applyBranchFilter($query)
    {
        return $query->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id));
    }

    /**
     * Calculate inventory stock value from Inventory Account and Cost of Goods Sold account
     * Inventory Account (asset): debit increases, credit decreases
     * Stock value = Inventory Account balance (debit - credit) up to the given date
     */
    protected function calculateStockValue(?string $date = null): float
    {
        $inventoryAccount = Account::where('name', 'Inventory')
            ->where('account_type', 'asset')
            ->first();

        if (! $inventoryAccount) {
            return 0.0;
        }

        $query = JournalEntry::query()
            ->where('account_id', $inventoryAccount->id)
            ->when($date, function ($q) use ($date) {
                return $q->where('date', '<=', $date);
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

        $this->applyBranchFilter($query);

        $result = $query->first();

        if (! $result) {
            return 0.0;
        }

        // For asset accounts, balance = debit - credit
        $balance = (float) $result->total_debit - (float) $result->total_credit;

        return max(0, $balance);
    }

    /**
     * Calculate net purchase from Journal Entries (Purchase account - Purchase Returns account)
     */
    protected function calculateNetPurchase(): float
    {
        $purchaseAccount = Account::where('name', 'Purchase')
            ->where('account_type', 'expense')
            ->first();

        $purchaseReturnAccount = Account::where('name', 'Purchase Returns')
            ->where('account_type', 'income')
            ->first();

        $totalPurchase = 0.0;
        $totalPurchaseReturn = 0.0;

        if ($purchaseAccount) {
            $query = JournalEntry::query()
                ->where('account_id', $purchaseAccount->id)
                ->whereBetween('date', [$this->start_date, $this->end_date])
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
                ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

            $this->applyBranchFilter($query);

            $result = $query->first();
            if ($result) {
                // For expense accounts, net = debit - credit
                $totalPurchase = max(0, (float) $result->total_debit - (float) $result->total_credit);
            }
        }

        if ($purchaseReturnAccount) {
            $query = JournalEntry::query()
                ->where('account_id', $purchaseReturnAccount->id)
                ->whereBetween('date', [$this->start_date, $this->end_date])
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
                ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

            $this->applyBranchFilter($query);

            $result = $query->first();
            if ($result) {
                // For income accounts, net = credit - debit
                $totalPurchaseReturn = max(0, (float) $result->total_credit - (float) $result->total_debit);
            }
        }

        return max(0, $totalPurchase - $totalPurchaseReturn);
    }

    /**
     * Calculate net sale from Journal Entries (Sale account - Sales Returns account)
     */
    protected function calculateNetSale(): float
    {
        $saleAccount = Account::where('name', 'Sale')
            ->where('account_type', 'income')
            ->first();

        $saleReturnAccount = Account::where('name', 'Sales Returns')
            ->where('account_type', 'expense')
            ->first();

        $totalSale = 0.0;
        $totalSaleReturn = 0.0;

        if ($saleAccount) {
            $query = JournalEntry::query()
                ->where('account_id', $saleAccount->id)
                ->whereBetween('date', [$this->start_date, $this->end_date])
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
                ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

            $this->applyBranchFilter($query);

            $result = $query->first();
            if ($result) {
                // For income accounts, net = credit - debit
                $totalSale = max(0, (float) $result->total_credit - (float) $result->total_debit);
            }
        }

        if ($saleReturnAccount) {
            $query = JournalEntry::query()
                ->where('account_id', $saleReturnAccount->id)
                ->whereBetween('date', [$this->start_date, $this->end_date])
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
                ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

            $this->applyBranchFilter($query);

            $result = $query->first();
            if ($result) {
                // For expense accounts, net = debit - credit
                $totalSaleReturn = max(0, (float) $result->total_debit - (float) $result->total_credit);
            }
        }

        return max(0, $totalSale - $totalSaleReturn);
    }

    /**
     * Calculate journal entry amounts for accounts using optimized aggregation
     * Returns array with account_id as key and net amount as value
     */
    protected function calculateJournalAmounts(array $accountIds, string $accountType): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $baseQuery = JournalEntry::query()
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->whereIn('account_id', $accountIds)
            ->select('account_id')
            ->selectRaw('SUM(debit) as total_debit')
            ->selectRaw('SUM(credit) as total_credit')
            ->groupBy('account_id');

        $this->applyBranchFilter($baseQuery);

        $results = $baseQuery->get()->mapWithKeys(function ($entry) use ($accountType) {
            $netAmount = $accountType === 'expense'
                ? (float) $entry->total_debit - (float) $entry->total_credit
                : (float) $entry->total_credit - (float) $entry->total_debit;

            return [$entry->account_id => max(0, $netAmount)];
        });

        return $results->toArray();
    }

    /**
     * Calculate direct expenses (COGS, Freight, Discount)
     */
    protected function calculateDirectExpense(): float
    {
        $accountIds = Account::whereIn('name', ['Cost of Goods Sold', 'Freight', 'Discount'])
            ->where('account_type', 'expense')
            ->pluck('id')
            ->toArray();

        $amounts = $this->calculateJournalAmounts($accountIds, 'expense');

        return array_sum($amounts);
    }

    /**
     * Calculate direct income (Purchase Discount)
     */
    protected function calculateDirectIncome(): float
    {
        $accountIds = Account::whereIn('name', ['Purchase Discount'])
            ->where('account_type', 'income')
            ->pluck('id')
            ->toArray();

        $amounts = $this->calculateJournalAmounts($accountIds, 'income');

        return array_sum($amounts);
    }

    /**
     * Calculate indirect expenses (all expenses except direct ones)
     */
    protected function calculateIndirectExpense(): float
    {
        $accountIds = Account::where('account_type', 'expense')
            ->whereNotIn('name', ['Cost of Goods Sold', 'Freight', 'Discount', 'Purchase'])
            ->pluck('id')
            ->toArray();

        $amounts = $this->calculateJournalAmounts($accountIds, 'expense');

        return array_sum($amounts);
    }

    /**
     * Calculate indirect income (all income except direct ones)
     */
    protected function calculateIndirectIncome(): float
    {
        $accountIds = Account::where('account_type', 'income')
            ->whereNotIn('name', ['Sale', 'Purchase Discount'])
            ->pluck('id')
            ->toArray();

        $amounts = $this->calculateJournalAmounts($accountIds, 'income');

        return array_sum($amounts);
    }

    public function render()
    {
        // Calculate stock values from Inventory Account in Journal Entries
        // Opening stock = Inventory Account balance at start_date
        // Closing stock = Inventory Account balance at end_date
        $openingStock = $this->calculateStockValue($this->start_date);
        $closingStock = $this->calculateStockValue($this->end_date);

        // Calculate net purchase and sale
        $netPurchase = $this->calculateNetPurchase();
        $netSale = $this->calculateNetSale();

        // Calculate direct expenses and income
        $directExpense = $this->calculateDirectExpense();
        $directIncome = $this->calculateDirectIncome();

        // Calculate Gross Profit/Loss (Top Section)
        $leftTotal1 = $openingStock + $netPurchase + $directExpense;
        $rightTotal1 = $netSale + $closingStock + $directIncome;
        $grossDifference = $rightTotal1 - $leftTotal1;
        $grossLoss = $grossDifference < 0 ? abs($grossDifference) : 0;
        $grossProfit = $grossDifference > 0 ? $grossDifference : 0;
        $leftTotal1 += $grossProfit;
        $rightTotal1 += $grossLoss;

        // Calculate indirect expenses and income
        $indirectExpense = $this->calculateIndirectExpense();
        $indirectIncome = $this->calculateIndirectIncome();

        // Calculate Net Profit/Loss (Bottom Section)
        $leftTotal2BeforeProfit = $grossLoss + $indirectExpense;
        $rightTotal2 = $grossProfit + $indirectIncome;
        $netDifference = $rightTotal2 - $leftTotal2BeforeProfit;
        $netProfitAmount = $netDifference > 0 ? $netDifference : 0;
        $netLossAmount = $netDifference < 0 ? abs($netDifference) : 0;
        $rightTotal2 += $netLossAmount;

        // Calculate final totals
        $leftTotal2 = $leftTotal2BeforeProfit + $netProfitAmount;

        return view('livewire.reports.profit-loss', [
            'openingStock' => $openingStock,
            'closingStock' => $closingStock,
            'netPurchase' => $netPurchase,
            'netSale' => $netSale,
            'directExpense' => $directExpense,
            'directIncome' => $directIncome,
            'grossLoss' => $grossLoss,
            'grossProfit' => $grossProfit,
            'indirectExpense' => $indirectExpense,
            'indirectIncome' => $indirectIncome,
            'netProfitAmount' => $netProfitAmount,
            'netLossAmount' => $netLossAmount,
            'leftTotal1' => $leftTotal1,
            'rightTotal1' => $rightTotal1,
            'leftTotal2' => $leftTotal2,
            'rightTotal2' => $rightTotal2,
            'branches' => $this->branches,
        ]);
    }
}
