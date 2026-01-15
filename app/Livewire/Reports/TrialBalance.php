<?php

namespace App\Livewire\Reports;

use App\Exports\TrialBalanceExport;
use App\Models\AccountCategory;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

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

    // Tree structure for each account type
    public $assetsTree = [];

    public $liabilitiesTree = [];

    public $equityTree = [];

    public $incomeTree = [];

    public $expensesTree = [];

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
        $this->branch_id = session('branch_id');
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

    /**
     * Get trial balance data - used by both render and export
     */
    protected function getTrialBalanceData(): array
    {
        $query = JournalEntry::query()
            ->selectRaw('
                account_id,
                accounts.name as account_name,
                accounts.account_type,
                accounts.account_category_id,
                COALESCE(SUM(journal_entries.debit), 0) as total_debit,
                COALESCE(SUM(journal_entries.credit), 0) as total_credit
            ')
            ->join('accounts', 'accounts.id', '=', 'account_id')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->when($this->branch_id, function ($query) {
                return $query->where('branch_id', $this->branch_id);
            })
            ->groupBy('account_id', 'accounts.name', 'accounts.account_type', 'accounts.account_category_id');

        $accounts = $query->get();

        // Build tree structures for each account type
        $assetsTree = $this->buildTreeStructure('asset', $accounts);
        $liabilitiesTree = $this->buildTreeStructure('liability', $accounts);
        $equityTree = $this->buildTreeStructure('equity', $accounts);
        $incomeTree = $this->buildTreeStructure('income', $accounts);
        $expensesTree = $this->buildTreeStructure('expense', $accounts);

        // Initialize totals
        $totalAssetsDebit = 0;
        $totalAssetsCredit = 0;
        $totalLiabilitiesDebit = 0;
        $totalLiabilitiesCredit = 0;
        $totalEquityDebit = 0;
        $totalEquityCredit = 0;
        $totalIncomeDebit = 0;
        $totalIncomeCredit = 0;
        $totalExpensesDebit = 0;
        $totalExpensesCredit = 0;

        // Process and categorize accounts
        $assets = [];
        $liabilities = [];
        $equity = [];
        $income = [];
        $expenses = [];

        foreach ($accounts as $account) {
            $debit = round((float) ($account->total_debit ?? 0), 2);
            $credit = round((float) ($account->total_credit ?? 0), 2);

            $balance = round($debit - $credit, 2);
            $accountData = [
                'code' => $account->account_code ?? null,
                'name' => $account->account_name,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];

            switch ($account->account_type) {
                case 'asset':
                    $assets[] = $accountData;
                    $totalAssetsDebit = bcadd($totalAssetsDebit, $debit, 2);
                    $totalAssetsCredit = bcadd($totalAssetsCredit, $credit, 2);
                    break;
                case 'liability':
                    $liabilities[] = $accountData;
                    $totalLiabilitiesDebit = bcadd($totalLiabilitiesDebit, $debit, 2);
                    $totalLiabilitiesCredit = bcadd($totalLiabilitiesCredit, $credit, 2);
                    break;
                case 'equity':
                    $equity[] = $accountData;
                    $totalEquityDebit = bcadd($totalEquityDebit, $debit, 2);
                    $totalEquityCredit = bcadd($totalEquityCredit, $credit, 2);
                    break;
                case 'income':
                    $income[] = $accountData;
                    $totalIncomeDebit = bcadd($totalIncomeDebit, $debit, 2);
                    $totalIncomeCredit = bcadd($totalIncomeCredit, $credit, 2);
                    break;
                case 'expense':
                    $expenses[] = $accountData;
                    $totalExpensesDebit = bcadd($totalExpensesDebit, $debit, 2);
                    $totalExpensesCredit = bcadd($totalExpensesCredit, $credit, 2);
                    break;
            }
        }

        // Calculate totals
        $totalAssets = bcsub($totalAssetsDebit, $totalAssetsCredit, 2);
        $totalLiabilities = bcsub($totalLiabilitiesCredit, $totalLiabilitiesDebit, 2);
        $netBalance = bcsub($totalAssets, $totalLiabilities, 2);

        // Calculate grand totals
        $totalDebit = array_sum([
            $totalAssetsDebit,
            $totalLiabilitiesDebit,
            $totalEquityDebit,
            $totalIncomeDebit,
            $totalExpensesDebit,
        ]);

        $totalCredit = array_sum([
            $totalAssetsCredit,
            $totalLiabilitiesCredit,
            $totalEquityCredit,
            $totalIncomeCredit,
            $totalExpensesCredit,
        ]);

        // Ensure proper decimal precision
        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'income' => $income,
            'expenses' => $expenses,
            'assetsTree' => $assetsTree,
            'liabilitiesTree' => $liabilitiesTree,
            'equityTree' => $equityTree,
            'incomeTree' => $incomeTree,
            'expensesTree' => $expensesTree,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'netBalance' => $netBalance,
            'totalAssetsDebit' => $totalAssetsDebit,
            'totalAssetsCredit' => $totalAssetsCredit,
            'totalLiabilitiesDebit' => $totalLiabilitiesDebit,
            'totalLiabilitiesCredit' => $totalLiabilitiesCredit,
            'totalEquityDebit' => $totalEquityDebit,
            'totalEquityCredit' => $totalEquityCredit,
            'totalIncomeDebit' => $totalIncomeDebit,
            'totalIncomeCredit' => $totalIncomeCredit,
            'totalExpensesDebit' => $totalExpensesDebit,
            'totalExpensesCredit' => $totalExpensesCredit,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ];
    }

    protected function loadTrialBalance()
    {
        $data = $this->getTrialBalanceData();

        // Populate component properties
        $this->assets = array_map(fn ($item) => (object) $item, $data['assets']);
        $this->liabilities = array_map(fn ($item) => (object) $item, $data['liabilities']);
        $this->equity = array_map(fn ($item) => (object) $item, $data['equity']);
        $this->income = array_map(fn ($item) => (object) $item, $data['income']);
        $this->expenses = array_map(fn ($item) => (object) $item, $data['expenses']);

        $this->assetsTree = $data['assetsTree'];
        $this->liabilitiesTree = $data['liabilitiesTree'];
        $this->equityTree = $data['equityTree'];
        $this->incomeTree = $data['incomeTree'];
        $this->expensesTree = $data['expensesTree'];

        $this->totalAssets = $data['totalAssets'];
        $this->totalLiabilities = $data['totalLiabilities'];
        $this->netBalance = $data['netBalance'];
        $this->totalAssetsDebit = $data['totalAssetsDebit'];
        $this->totalAssetsCredit = $data['totalAssetsCredit'];
        $this->totalLiabilitiesDebit = $data['totalLiabilitiesDebit'];
        $this->totalLiabilitiesCredit = $data['totalLiabilitiesCredit'];
        $this->totalEquityDebit = $data['totalEquityDebit'];
        $this->totalEquityCredit = $data['totalEquityCredit'];
        $this->totalIncomeDebit = $data['totalIncomeDebit'];
        $this->totalIncomeCredit = $data['totalIncomeCredit'];
        $this->totalExpensesDebit = $data['totalExpensesDebit'];
        $this->totalExpensesCredit = $data['totalExpensesCredit'];
        $this->totalDebit = $data['totalDebit'];
        $this->totalCredit = $data['totalCredit'];
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

    /**
     * Export Trial Balance report to Excel
     */
    public function export()
    {
        try {
            $reportData = $this->getTrialBalanceData();
            $branchName = $this->getBranchName();

            $fileName = 'Trial_Balance_Report_'.$this->start_date.'_to_'.$this->end_date.'_'.now()->format('Y-m-d_H-i-s').'.xlsx';

            return Excel::download(
                new TrialBalanceExport($reportData, $this->start_date, $this->end_date, $branchName),
                $fileName
            );
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
        }
    }

    /**
     * Build tree structure for account type with categories
     * Groups accounts by their account category
     */
    protected function buildTreeStructure(string $accountType, $accounts)
    {
        // Get all accounts of this type
        $typeAccounts = $accounts->where('account_type', $accountType);

        if ($typeAccounts->isEmpty()) {
            return [];
        }

        // Get all categories for this account type
        $categoryIds = $typeAccounts->pluck('account_category_id')->filter()->unique();

        if ($categoryIds->isEmpty()) {
            // No categories, return flat list
            $uncategorized = [];
            foreach ($typeAccounts as $account) {
                $debit = round((float) ($account->total_debit ?? 0), 2);
                $credit = round((float) ($account->total_credit ?? 0), 2);
                $balance = round($debit - $credit, 2);
                $uncategorized[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                ];
            }

            return ['uncategorized' => $uncategorized];
        }

        // Get categories with their hierarchy - include parent categories even if they don't have direct accounts
        $categories = AccountCategory::whereIn('id', $categoryIds)
            ->with(['parent', 'children'])
            ->get();

        // Also fetch parent categories that might not be directly referenced but are needed for hierarchy
        $parentIds = $categories->pluck('parent_id')->filter()->unique();
        if ($parentIds->isNotEmpty()) {
            $parentCategories = AccountCategory::whereIn('id', $parentIds)
                ->whereNotIn('id', $categoryIds)
                ->with(['parent', 'children'])
                ->get();
            $categories = $categories->merge($parentCategories);
        }

        // Build category map and organize by hierarchy
        $categoryMap = [];
        $masterCategories = [];
        $subCategories = [];

        foreach ($categories as $category) {
            $categoryMap[$category->id] = $category;

            if ($category->parent_id) {
                if (! isset($subCategories[$category->parent_id])) {
                    $subCategories[$category->parent_id] = [];
                }
                $subCategories[$category->parent_id][] = $category;
            } else {
                $masterCategories[$category->id] = $category;
            }
        }

        // Build tree structure - ensure all accounts are grouped by category
        $tree = [];
        $categorizedAccountIds = [];

        // Process master categories in sorted order
        $sortedMasterCategories = collect($masterCategories)->sortBy('name');

        foreach ($sortedMasterCategories as $masterId => $masterCategory) {
            $masterDebit = 0;
            $masterCredit = 0;
            $groups = [];
            $directAccounts = [];

            // Get accounts directly under master category
            $directAccountList = $typeAccounts->where('account_category_id', $masterId);
            foreach ($directAccountList as $account) {
                $debit = round((float) ($account->total_debit ?? 0), 2);
                $credit = round((float) ($account->total_credit ?? 0), 2);
                $masterDebit += $debit;
                $masterCredit += $credit;
                $categorizedAccountIds[] = $account->account_id;

                $balance = round($debit - $credit, 2);
                $directAccounts[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                ];
            }

            // Process sub-categories (groups) - sorted by name
            if (isset($subCategories[$masterId])) {
                $sortedSubCategories = collect($subCategories[$masterId])->sortBy('name');

                foreach ($sortedSubCategories as $subCategory) {
                    $groupDebit = 0;
                    $groupCredit = 0;
                    $groupAccounts = [];

                    $subAccountList = $typeAccounts->where('account_category_id', $subCategory->id);
                    foreach ($subAccountList as $account) {
                        $debit = round((float) ($account->total_debit ?? 0), 2);
                        $credit = round((float) ($account->total_credit ?? 0), 2);
                        $groupDebit += $debit;
                        $groupCredit += $credit;
                        $masterDebit += $debit;
                        $masterCredit += $credit;
                        $categorizedAccountIds[] = $account->account_id;

                        $balance = round($debit - $credit, 2);
                        $groupAccounts[] = [
                            'id' => $account->account_id,
                            'name' => $account->account_name,
                            'debit' => $debit,
                            'credit' => $credit,
                            'balance' => $balance,
                        ];
                    }

                    if (! empty($groupAccounts)) {
                        $groupBalance = round($groupDebit - $groupCredit, 2);
                        $groups[] = [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                            'debit' => $groupDebit,
                            'credit' => $groupCredit,
                            'balance' => $groupBalance,
                            'accounts' => $groupAccounts,
                        ];
                    }
                }
            }

            // Only add master category if it has accounts or groups
            if (! empty($directAccounts) || ! empty($groups)) {
                $masterBalance = round($masterDebit - $masterCredit, 2);
                $tree[] = [
                    'id' => $masterCategory->id,
                    'name' => $masterCategory->name,
                    'debit' => $masterDebit,
                    'credit' => $masterCredit,
                    'balance' => $masterBalance,
                    'groups' => $groups,
                    'directAccounts' => $directAccounts,
                ];
            }
        }

        // Handle sub-categories that might be orphaned (parent not in master categories but exists)
        // This handles cases where a sub-category's parent exists but wasn't included above
        foreach ($subCategories as $parentId => $subCats) {
            // If parent is not in masterCategories but exists in categoryMap, it might be a nested category
            if (! isset($masterCategories[$parentId]) && isset($categoryMap[$parentId])) {
                $parentCategory = $categoryMap[$parentId];

                // Check if this parent category has accounts
                $parentAccountList = $typeAccounts->where('account_category_id', $parentId);
                if ($parentAccountList->isNotEmpty()) {
                    // This parent category has accounts, treat it as a master category
                    $masterDebit = 0;
                    $masterCredit = 0;
                    $groups = [];
                    $directAccounts = [];

                    foreach ($parentAccountList as $account) {
                        $debit = round((float) ($account->total_debit ?? 0), 2);
                        $credit = round((float) ($account->total_credit ?? 0), 2);
                        $masterDebit += $debit;
                        $masterCredit += $credit;
                        $categorizedAccountIds[] = $account->account_id;

                        $balance = round($debit - $credit, 2);
                        $directAccounts[] = [
                            'id' => $account->account_id,
                            'name' => $account->account_name,
                            'debit' => $debit,
                            'credit' => $credit,
                            'balance' => $balance,
                        ];
                    }

                    // Process sub-categories under this parent
                    foreach ($subCats as $subCategory) {
                        $groupDebit = 0;
                        $groupCredit = 0;
                        $groupAccounts = [];

                        $subAccountList = $typeAccounts->where('account_category_id', $subCategory->id);
                        foreach ($subAccountList as $account) {
                            $debit = round((float) ($account->total_debit ?? 0), 2);
                            $credit = round((float) ($account->total_credit ?? 0), 2);
                            $groupDebit += $debit;
                            $groupCredit += $credit;
                            $masterDebit += $debit;
                            $masterCredit += $credit;
                            $categorizedAccountIds[] = $account->account_id;

                            $balance = round($debit - $credit, 2);
                            $groupAccounts[] = [
                                'id' => $account->account_id,
                                'name' => $account->account_name,
                                'debit' => $debit,
                                'credit' => $credit,
                                'balance' => $balance,
                            ];
                        }

                        if (! empty($groupAccounts)) {
                            $groupBalance = round($groupDebit - $groupCredit, 2);
                            $groups[] = [
                                'id' => $subCategory->id,
                                'name' => $subCategory->name,
                                'debit' => $groupDebit,
                                'credit' => $groupCredit,
                                'balance' => $groupBalance,
                                'accounts' => $groupAccounts,
                            ];
                        }
                    }

                    if (! empty($directAccounts) || ! empty($groups)) {
                        $masterBalance = round($masterDebit - $masterCredit, 2);
                        $tree[] = [
                            'id' => $parentCategory->id,
                            'name' => $parentCategory->name,
                            'debit' => $masterDebit,
                            'credit' => $masterCredit,
                            'balance' => $masterBalance,
                            'groups' => $groups,
                            'directAccounts' => $directAccounts,
                        ];
                    }
                }
            }
        }

        // Handle uncategorized accounts (accounts without category_id or with invalid category_id)
        $uncategorized = [];

        // Find accounts that are not in any category or have invalid category
        foreach ($typeAccounts as $account) {
            if (! in_array($account->account_id, $categorizedAccountIds)) {
                $debit = round((float) ($account->total_debit ?? 0), 2);
                $credit = round((float) ($account->total_credit ?? 0), 2);

                $balance = round($debit - $credit, 2);
                $uncategorized[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                ];
            }
        }

        if (! empty($uncategorized)) {
            $tree['uncategorized'] = $uncategorized;
        }

        return $tree;
    }

    public function render()
    {
        return view('livewire.reports.trial-balance');
    }
}
