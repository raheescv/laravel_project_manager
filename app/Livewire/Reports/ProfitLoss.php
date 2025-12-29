<?php

namespace App\Livewire\Reports;

use App\Exports\ProfitLossExport;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLoss extends Component
{
    public $start_date;

    public $end_date;

    public $branch_id;

    public $branches = [];

    public $period = 'monthly';

    public $expandedGroups = []; // Track which groups are expanded

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
     * Toggle group expansion
     */
    public function toggleGroup($groupId)
    {
        if (in_array($groupId, $this->expandedGroups)) {
            $this->expandedGroups = array_values(array_diff($this->expandedGroups, [$groupId]));
        } else {
            $this->expandedGroups[] = $groupId;
        }
    }

    /**
     * Fetch data - triggers re-render with current filter values
     */
    public function fetchData()
    {
        // This method triggers a re-render with current filter values
        // No additional logic needed as render() will use current property values
    }

    /**
     * Reset filters to default values
     */
    public function resetFilters()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->branch_id = '';
        $this->period = 'monthly';
        $this->expandedGroups = [];
    }

    /**
     * Export Profit & Loss report to Excel
     */
    public function export()
    {
        try {
            $reportData = $this->getReportData();
            $branchName = $this->getBranchName();

            $fileName = 'Profit_Loss_Report_'.$this->start_date.'_to_'.$this->end_date.'_'.now()->format('Y-m-d_H-i-s').'.xlsx';

            return Excel::download(
                new ProfitLossExport($reportData, $this->start_date, $this->end_date, $branchName),
                $fileName
            );
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
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
     * Calculate inventory stock value from Inventory Account
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

        $totalPurchase = 0.0;
        $purchaseAccount = Account::where('name', 'Purchase') ->where('account_type', 'expense') ->first();
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

        $inventoryAccount = Account::where('name', 'Inventory') ->where('account_type', 'asset') ->first();
        if ($inventoryAccount) {
            $query = JournalEntry::query()
                ->where('account_id', $inventoryAccount->id)
                ->whereBetween('date', [$this->start_date, $this->end_date])
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
                ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

            $this->applyBranchFilter($query);

            $result = $query->first();
            if ($result) {
                // For expense accounts, net = debit - credit
                $totalPurchase += $result->total_debit;
            }
        }

        $totalPurchaseReturn = 0.0;
        $purchaseReturnAccount = Account::where('name', 'Purchase Returns') ->where('account_type', 'income') ->first();
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
     * Calculate journal entry amount for a single account
     */
    protected function calculateAccountAmount(int $accountId, string $accountType): float
    {
        $query = JournalEntry::query()
            ->where('account_id', $accountId)
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(credit), 0) as total_credit');

        $this->applyBranchFilter($query);

        $result = $query->first();

        if (! $result) {
            return 0.0;
        }

        $netAmount = $accountType === 'expense'
            ? (float) $result->total_debit - (float) $result->total_credit
            : (float) $result->total_credit - (float) $result->total_debit;

        return max(0, $netAmount);
    }

    /**
     * Build hierarchical structure for income/expense categories
     */
    protected function buildCategoryStructure(string $accountType): array
    {
        // Get master categories based on account type
        $masterCategoryNames = $accountType === 'income' ? ['Direct Income', 'Indirect Income'] : ['Direct Expense', 'Indirect Expense'];

        $structure = [];
        $sale_id = Account::where('name', 'Sale')->first()?->id;

        // Get IDs of special accounts that are handled separately
        $specialAccountIds = Account::whereIn('name', ['Sale', 'Purchase', 'Purchase Returns', 'Sales Returns'])
            ->where('account_type', $accountType)
            ->pluck('id')
            ->toArray();

        foreach ($masterCategoryNames as $masterName) {
            $masterCategory = AccountCategory::where('name', $masterName)
                ->whereNull('parent_id')
                ->with(['children.accounts' => function ($query) use ($accountType) {
                    $query->where('account_type', $accountType);
                }, 'accounts' => function ($query) use ($accountType) {
                    $query->where('account_type', $accountType);
                }])
                ->first();

            if (! $masterCategory) {
                continue;
            }

            $masterTotal = 0.0;
            $groups = [];
            $directAccounts = [];
            // Get accounts directly under master category (without a group)
            foreach ($masterCategory->accounts as $account) {
                $amount = $this->calculateAccountAmount($account->id, $accountType);
                $masterTotal += $amount;

                $directAccounts[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }

            // Get accounts under groups
            foreach ($masterCategory->children as $group) {
                $groupTotal = 0.0;
                $accounts = [];
                foreach ($group->accounts as $account) {
                    if ($account->id === $sale_id) {
                        continue;
                    }
                    if ($account->id === 10) {
                        continue;
                    }
                    $amount = $this->calculateAccountAmount($account->id, $accountType);
                    $groupTotal += $amount;

                    $accounts[] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'amount' => $amount,
                    ];
                }

                $masterTotal += $groupTotal;

                $groups[] = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'total' => $groupTotal,
                    'accounts' => $accounts,
                ];
            }
            $structure[] = [
                'id' => $masterCategory->id,
                'name' => $masterCategory->name,
                'total' => $masterTotal,
                'groups' => $groups,
                'directAccounts' => $directAccounts,
            ];
        }

        // Add un categorized accounts to Direct Expense/Direct Income
        $directCategoryName = $accountType === 'income' ? 'Indirect Income' : 'Indirect Expense';

        // Find un categorized accounts (accounts with null account_category_id)
        $unCategorizedAccounts = Account::where('account_type', $accountType)
            ->whereNull('account_category_id')
            ->whereNotIn('id', $specialAccountIds)
            ->get();

        $unCategorizedTotal = 0.0;
        $unCategorizedAccountsList = [];

        foreach ($unCategorizedAccounts as $account) {
            $amount = $this->calculateAccountAmount($account->id, $accountType);
            $unCategorizedTotal += $amount;

            $unCategorizedAccountsList[] = [
                'id' => $account->id,
                'name' => $account->name,
                'amount' => $amount,
            ];
        }

        // Add un categorized accounts to Direct Expense/Direct Income structure
        if (count($unCategorizedAccountsList) > 0) {
            // Find or create the Direct Expense/Direct Income entry in structure
            $directCategoryFound = false;
            foreach ($structure as $index => $category) {
                if ($category['name'] === $directCategoryName) {
                    // Add un categorized accounts to directAccounts
                    $structure[$index]['directAccounts'] = array_merge(
                        $structure[$index]['directAccounts'],
                        $unCategorizedAccountsList
                    );
                    // Update total
                    $structure[$index]['total'] += $unCategorizedTotal;
                    $directCategoryFound = true;
                    break;
                }
            }

            // If Direct Expense/Direct Income category doesn't exist in structure, create it
            if (! $directCategoryFound) {
                $directCategory = AccountCategory::where('name', $directCategoryName)
                    ->whereNull('parent_id')
                    ->first();

                $structure[] = [
                    'id' => $directCategory?->id ?? 0,
                    'name' => $directCategoryName,
                    'total' => $unCategorizedTotal,
                    'groups' => [],
                    'directAccounts' => $unCategorizedAccountsList,
                ];
            }
        }

        return $structure;
    }

    /**
     * Get all report data - used by both render and export
     */
    protected function getReportData(): array
    {
        // Calculate stock values
        $openingStock = $this->calculateStockValue($this->start_date);
        $closingStock = $this->calculateStockValue($this->end_date);

        // Calculate net purchase and sale
        $netPurchase = $this->calculateNetPurchase();
        $netSale = $this->calculateNetSale();

        // Build hierarchical structures
        $directIncomeStructure = $this->buildCategoryStructure('income');
        $directExpenseStructure = $this->buildCategoryStructure('expense');

        // Extract income/expense totals
        $totals = $this->extractIncomeExpenseTotals($directIncomeStructure, $directExpenseStructure);

        // Calculate profit/loss
        $profitLoss = $this->calculateProfitLoss(
            $openingStock,
            $closingStock,
            $netPurchase,
            $netSale,
            $totals['directExpense'],
            $totals['directIncome'],
            $totals['indirectExpense'],
            $totals['indirectIncome']
        );

        return array_merge([
            'openingStock' => $openingStock,
            'closingStock' => $closingStock,
            'netPurchase' => $netPurchase,
            'netSale' => $netSale,
            'directIncomeStructure' => $directIncomeStructure,
            'directExpenseStructure' => $directExpenseStructure,
        ], $totals, $profitLoss);
    }

    /**
     * Extract income and expense totals from category structures
     */
    protected function extractIncomeExpenseTotals(array $incomeStructure, array $expenseStructure): array
    {
        $directIncome = 0.0;
        $indirectIncome = 0.0;
        $directExpense = 0.0;
        $indirectExpense = 0.0;

        foreach ($incomeStructure as $master) {
            if ($master['name'] === 'Direct Income') {
                $directIncome = $master['total'];
            } elseif ($master['name'] === 'Indirect Income') {
                $indirectIncome = $master['total'];
            }
        }

        foreach ($expenseStructure as $master) {
            if ($master['name'] === 'Direct Expense') {
                $directExpense = $master['total'];
            } elseif ($master['name'] === 'Indirect Expense') {
                $indirectExpense = $master['total'];
            }
        }

        return [
            'directIncome' => $directIncome,
            'indirectIncome' => $indirectIncome,
            'directExpense' => $directExpense,
            'indirectExpense' => $indirectExpense,
        ];
    }

    /**
     * Calculate gross and net profit/loss
     */
    protected function calculateProfitLoss(
        float $openingStock,
        float $closingStock,
        float $netPurchase,
        float $netSale,
        float $directExpense,
        float $directIncome,
        float $indirectExpense,
        float $indirectIncome
    ): array {
        // Calculate Gross Profit/Loss
        $leftTotal1 = $openingStock + $netPurchase + $directExpense;
        $rightTotal1 = $netSale + $closingStock + $directIncome;
        $grossDifference = $rightTotal1 - $leftTotal1;
        $grossLoss = $grossDifference < 0 ? abs($grossDifference) : 0;
        $grossProfit = $grossDifference > 0 ? $grossDifference : 0;
        $leftTotal1 += $grossProfit;
        $rightTotal1 += $grossLoss;

        // Calculate Net Profit/Loss
        $leftTotal2BeforeProfit = $grossLoss + $indirectExpense;
        $rightTotal2 = $grossProfit + $indirectIncome;
        $netDifference = $rightTotal2 - $leftTotal2BeforeProfit;
        $netProfitAmount = $netDifference > 0 ? $netDifference : 0;
        $netLossAmount = $netDifference < 0 ? abs($netDifference) : 0;
        $rightTotal2 += $netLossAmount;
        $leftTotal2 = $leftTotal2BeforeProfit + $netProfitAmount;

        return [
            'grossLoss' => $grossLoss,
            'grossProfit' => $grossProfit,
            'netProfitAmount' => $netProfitAmount,
            'netLossAmount' => $netLossAmount,
            'leftTotal1' => $leftTotal1,
            'rightTotal1' => $rightTotal1,
            'leftTotal2' => $leftTotal2,
            'rightTotal2' => $rightTotal2,
        ];
    }

    /**
     * Get branch name for display
     */
    protected function getBranchName(): ?string
    {
        if (! $this->branch_id) {
            return null;
        }

        $branch = Branch::find($this->branch_id);

        return $branch ? $branch->name : null;
    }

    public function render()
    {
        $reportData = $this->getReportData();

        return view('livewire.reports.profit-loss', array_merge($reportData, [
            'branches' => $this->branches,
        ]));
    }
}
