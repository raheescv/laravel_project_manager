<?php

namespace App\Livewire\Reports;

use App\Exports\ProfitLossExport;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    public $expandedGroups = [];

    public function mount()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');

        $branch_ids = Auth::user()->branches->pluck('branch_id', 'branch_id')->toArray();
        $this->branches = Branch::whereIn('id', $branch_ids)->pluck('name', 'id')->toArray();
        $this->branch_id = session('branch_id');
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

    public function toggleGroup($groupId)
    {
        if (in_array($groupId, $this->expandedGroups)) {
            $this->expandedGroups = array_values(array_diff($this->expandedGroups, [$groupId]));
        } else {
            $this->expandedGroups[] = $groupId;
        }
    }

    public function fetchData()
    {
        // Triggers re-render with current filter values
    }

    public function resetFilters()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->branch_id = '';
        $this->period = 'monthly';
        $this->expandedGroups = [];
    }

    public function export()
    {
        try {
            $reportData = $this->getReportData();
            $branchName = $this->branch_id ? (Branch::find($this->branch_id)?->name) : null;
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
     * Get all report data — optimized with batch queries.
     */
    protected function getReportData(): array
    {
        // 1. Load all income/expense/asset accounts with categories in one query
        $allAccounts = Account::whereIn('account_type', ['income', 'expense', 'asset'])
            ->get()
            ->keyBy('id');

        // 2. Identify special accounts (handled separately in T-account format)
        $specialMap = $this->resolveSpecialAccounts($allAccounts);
        $specialAccountIds = array_filter(array_values($specialMap));

        // 3. Fetch ALL period balances in a single query
        $periodBalances = $this->fetchPeriodBalances();

        // 4. Calculate Opening Stock (balance BEFORE start date) and Closing Stock
        [$openingStock, $closingStock] = $this->calculateStockValues($specialMap['inventory']);

        // 5. Calculate Net Purchase and Net Sale from pre-fetched balances
        $netPurchase = $this->calculateNetPurchase2($specialMap, $periodBalances);
        $netSale = $this->calculateNetSale2($specialMap, $periodBalances);

        // 6. Build category structures using pre-fetched balances (no N+1)
        $incomeStructure = $this->buildCategoryStructure2('income', $periodBalances, $specialAccountIds);
        $expenseStructure = $this->buildCategoryStructure2('expense', $periodBalances, $specialAccountIds);

        // 7. Extract totals
        $totals = $this->extractTotals($incomeStructure, $expenseStructure);

        // 8. Calculate Gross and Net Profit/Loss
        $profitLoss = $this->calculateProfitLoss(
            $openingStock, $closingStock, $netPurchase, $netSale,
            $totals['directExpense'], $totals['directIncome'],
            $totals['indirectExpense'], $totals['indirectIncome']
        );

        return array_merge([
            'openingStock' => $openingStock,
            'closingStock' => $closingStock,
            'netPurchase' => $netPurchase,
            'netSale' => $netSale,
            'directIncomeStructure' => $incomeStructure,
            'directExpenseStructure' => $expenseStructure,
        ], $totals, $profitLoss);
    }

    /**
     * Resolve special account IDs from pre-loaded accounts.
     */
    protected function resolveSpecialAccounts(Collection $accounts): array
    {
        $find = function (string $name, string $type, ?string $slug = null) use ($accounts): ?int {
            return $accounts->first(function ($a) use ($name, $type, $slug) {
                if ($slug && ($a->slug ?? '') === $slug && $a->account_type === $type) {
                    return true;
                }

                return strtolower($a->getAttributes()['name']) === strtolower($name) && $a->account_type === $type;
            })?->id;
        };

        return [
            'inventory' => $find('Inventory', 'asset'),
            'purchase' => $find('Purchase', 'expense'),
            'purchase_returns' => $find('Purchase Returns', 'income'),
            'sale' => $find('Sale', 'income'),
            'sales_returns' => $find('Sales Returns', 'expense'),
            'cogs' => $find('Cost Of Goods Sold', 'expense', 'cost_of_goods_sold'),
        ];
    }

    /**
     * Fetch all journal entry balances for the period in ONE query.
     */
    protected function fetchPeriodBalances(): Collection
    {
        return JournalEntry::query()
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Get the net balance for an account from pre-fetched period data.
     */
    protected function getAccountBalance(int $accountId, string $accountType, Collection $periodBalances): float
    {
        $balance = $periodBalances->get($accountId);
        if (! $balance) {
            return 0.0;
        }

        // Expense/Asset: natural debit balance. Income/Liability: natural credit balance.
        $net = ($accountType === 'expense' || $accountType === 'asset')
            ? (float) $balance->total_debit - (float) $balance->total_credit
            : (float) $balance->total_credit - (float) $balance->total_debit;

        return max(0, $net);
    }

    /**
     * Calculate Opening Stock and Closing Stock.
     * Opening Stock = Inventory balance BEFORE start_date (exclusive).
     * Closing Stock = Inventory balance up to and including end_date.
     */
    protected function calculateStockValues(?int $inventoryId): array
    {
        if (! $inventoryId) {
            return [0.0, 0.0];
        }

        $branchFilter = fn ($q) => $q->when($this->branch_id, fn ($q2) => $q2->where('branch_id', $this->branch_id));

        // Opening stock: all entries BEFORE start date (not including start date)
        $opening = JournalEntry::query()
            ->where('account_id', $inventoryId)
            ->where('date', '<', $this->start_date)
            ->tap($branchFilter)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        // Closing stock: all entries up to and including end date
        $closing = JournalEntry::query()
            ->where('account_id', $inventoryId)
            ->where('date', '<=', $this->end_date)
            ->tap($branchFilter)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $openingStock = max(0, (float) ($opening->total_debit ?? 0) - (float) ($opening->total_credit ?? 0));
        $closingStock = max(0, (float) ($closing->total_debit ?? 0) - (float) ($closing->total_credit ?? 0));

        return [$openingStock, $closingStock];
    }

    /**
     * Net Purchase = Purchase Account - Purchase Returns Account.
     * Inventory is NOT added here — it's already captured via Opening/Closing Stock.
     */
    protected function calculateNetPurchase2(array $specialMap, Collection $periodBalances): float
    {
        $purchase = 0.0;
        if ($specialMap['purchase']) {
            $purchase = $this->getAccountBalance($specialMap['purchase'], 'expense', $periodBalances);
        }

        $returns = 0.0;
        if ($specialMap['purchase_returns']) {
            $returns = $this->getAccountBalance($specialMap['purchase_returns'], 'income', $periodBalances);
        }

        return max(0, $purchase - $returns);
    }

    /**
     * Net Sale = Sale Account - Sales Returns Account.
     */
    protected function calculateNetSale2(array $specialMap, Collection $periodBalances): float
    {
        $sale = 0.0;
        if ($specialMap['sale']) {
            $sale = $this->getAccountBalance($specialMap['sale'], 'income', $periodBalances);
        }

        $returns = 0.0;
        if ($specialMap['sales_returns']) {
            $returns = $this->getAccountBalance($specialMap['sales_returns'], 'expense', $periodBalances);
        }

        return max(0, $sale - $returns);
    }

    /**
     * Build hierarchical category structure using pre-fetched balances.
     * Excludes special accounts (Sale, Purchase, etc.) to prevent double-counting.
     */
    protected function buildCategoryStructure2(string $accountType, Collection $periodBalances, array $specialAccountIds): array
    {
        $masterCategoryNames = $accountType === 'income'
            ? ['Direct Income', 'Indirect Income']
            : ['Direct Expense', 'Indirect Expense'];

        $structure = [];

        // Pre-load all master categories with children and accounts in one eager load
        $masterCategories = AccountCategory::whereIn('name', $masterCategoryNames)
            ->whereNull('parent_id')
            ->with([
                'children.accounts' => fn ($q) => $q->where('account_type', $accountType),
                'accounts' => fn ($q) => $q->where('account_type', $accountType),
            ])
            ->get()
            ->keyBy('name');

        foreach ($masterCategoryNames as $masterName) {
            $masterCategory = $masterCategories->get($masterName);
            if (! $masterCategory) {
                continue;
            }

            $masterTotal = 0.0;
            $groups = [];
            $directAccounts = [];

            // Accounts directly under master category
            foreach ($masterCategory->accounts as $account) {
                if (in_array($account->id, $specialAccountIds)) {
                    continue;
                }
                $amount = $this->getAccountBalance($account->id, $accountType, $periodBalances);
                $masterTotal += $amount;
                $directAccounts[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }

            // Accounts under sub-groups
            foreach ($masterCategory->children as $group) {
                $groupTotal = 0.0;
                $accounts = [];

                foreach ($group->accounts as $account) {
                    if (in_array($account->id, $specialAccountIds)) {
                        continue;
                    }
                    $amount = $this->getAccountBalance($account->id, $accountType, $periodBalances);
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

        // Add uncategorized accounts to Indirect Income/Expense
        $this->appendUncategorizedAccounts($structure, $accountType, $periodBalances, $specialAccountIds);

        return $structure;
    }

    /**
     * Append uncategorized accounts to the Indirect category.
     */
    protected function appendUncategorizedAccounts(
        array &$structure,
        string $accountType,
        Collection $periodBalances,
        array $specialAccountIds
    ): void {
        $uncategorized = Account::where('account_type', $accountType)
            ->whereNull('account_category_id')
            ->whereNotIn('id', $specialAccountIds)
            ->get();

        if ($uncategorized->isEmpty()) {
            return;
        }

        $uncatTotal = 0.0;
        $uncatList = [];

        foreach ($uncategorized as $account) {
            $amount = $this->getAccountBalance($account->id, $accountType, $periodBalances);
            $uncatTotal += $amount;
            $uncatList[] = [
                'id' => $account->id,
                'name' => $account->name,
                'amount' => $amount,
            ];
        }

        if (empty($uncatList)) {
            return;
        }

        $targetName = $accountType === 'income' ? 'Indirect Income' : 'Indirect Expense';
        $found = false;

        foreach ($structure as $index => $category) {
            if ($category['name'] === $targetName) {
                $structure[$index]['directAccounts'] = array_merge($structure[$index]['directAccounts'], $uncatList);
                $structure[$index]['total'] += $uncatTotal;
                $found = true;
                break;
            }
        }

        if (! $found) {
            $indirectCategory = AccountCategory::where('name', $targetName)->whereNull('parent_id')->first();
            $structure[] = [
                'id' => $indirectCategory?->id ?? 0,
                'name' => $targetName,
                'total' => $uncatTotal,
                'groups' => [],
                'directAccounts' => $uncatList,
            ];
        }
    }

    /**
     * Extract direct/indirect income and expense totals from category structures.
     */
    protected function extractTotals(array $incomeStructure, array $expenseStructure): array
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

        return compact('directIncome', 'indirectIncome', 'directExpense', 'indirectExpense');
    }

    /**
     * Calculate Gross and Net Profit/Loss using the T-Account method.
     *
     * Trading Account (Gross):
     *   Left:  Opening Stock + Net Purchase + Direct Expense + [Gross Profit]
     *   Right: Net Sale + Closing Stock + Direct Income + [Gross Loss]
     *
     * P&L Account (Net):
     *   Left:  Gross Loss + Indirect Expense + [Net Profit]
     *   Right: Gross Profit + Indirect Income + [Net Loss]
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
        // Trading Account
        $leftTotal1 = $openingStock + $netPurchase + $directExpense;
        $rightTotal1 = $netSale + $closingStock + $directIncome;
        $grossDifference = $rightTotal1 - $leftTotal1;
        $grossProfit = max(0, $grossDifference);
        $grossLoss = max(0, -$grossDifference);
        $leftTotal1 += $grossProfit;
        $rightTotal1 += $grossLoss;

        // Profit & Loss Account
        $leftTotal2Base = $grossLoss + $indirectExpense;
        $rightTotal2Base = $grossProfit + $indirectIncome;
        $netDifference = $rightTotal2Base - $leftTotal2Base;
        $netProfitAmount = max(0, $netDifference);
        $netLossAmount = max(0, -$netDifference);
        $leftTotal2 = $leftTotal2Base + $netProfitAmount;
        $rightTotal2 = $rightTotal2Base + $netLossAmount;

        return compact(
            'grossLoss', 'grossProfit',
            'netProfitAmount', 'netLossAmount',
            'leftTotal1', 'rightTotal1',
            'leftTotal2', 'rightTotal2'
        );
    }

    public function render()
    {
        $reportData = $this->getReportData();

        return view('livewire.reports.profit-loss', array_merge($reportData, [
            'branches' => $this->branches,
        ]));
    }
}
