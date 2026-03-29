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

    public $selected_account_ids = [];

    // Flat account lists (fallback when no tree)
    public $assets = [];

    public $liabilities = [];

    public $equity = [];

    public $income = [];

    public $expenses = [];

    public $other = [];

    // Tree structures
    public $assetsTree = [];

    public $liabilitiesTree = [];

    public $equityTree = [];

    public $incomeTree = [];

    public $expensesTree = [];

    public $otherTree = [];

    // Summary totals
    public $totalAssets = 0;

    public $totalLiabilities = 0;

    public $netBalance = 0;

    // Section debit/credit totals
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

    public $totalOtherDebit = 0;

    public $totalOtherCredit = 0;

    // Grand totals
    public $totalDebit = 0;

    public $totalCredit = 0;

    public function mount()
    {
        $this->branch_id = session('branch_id');
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadTrialBalance();
    }

    public function updatedPeriod()
    {
        $periods = [
            'monthly' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'quarterly' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'yearly' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'previous_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
        ];

        if (isset($periods[$this->period])) {
            [$start, $end] = $periods[$this->period];
            $this->start_date = $start->format('Y-m-d');
            $this->end_date = $end->format('Y-m-d');
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

    public function updatedSelectedAccountIds()
    {
        $this->loadTrialBalance();
    }

    /**
     * Get trial balance data - used by both render and export
     */
    public function getTrialBalanceData(): array
    {
        $accounts = JournalEntry::query()
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
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->when(! empty($this->selected_account_ids), fn ($q) => $q->whereIn('account_id', $this->selected_account_ids))
            ->groupBy('account_id', 'accounts.name', 'accounts.account_type', 'accounts.account_category_id')
            ->get();

        // Build trees for typed accounts + "other" for untyped
        $types = ['asset', 'liability', 'equity', 'income', 'expense'];
        $trees = [];
        foreach ($types as $type) {
            $trees[$type] = $this->buildTreeStructure($type, $accounts);
        }

        // Collect accounts with empty/null account_type as "other"
        $otherAccounts = $accounts->filter(fn ($a) => empty($a->account_type));
        $otherFlat = [];
        $otherDebit = 0;
        $otherCredit = 0;
        foreach ($otherAccounts as $account) {
            $d = round((float) ($account->total_debit ?? 0), 2);
            $c = round((float) ($account->total_credit ?? 0), 2);
            $otherDebit = bcadd($otherDebit, $d, 2);
            $otherCredit = bcadd($otherCredit, $c, 2);
            $otherFlat[] = [
                'id' => $account->account_id,
                'name' => $account->account_name,
                'debit' => $d,
                'credit' => $c,
                'balance' => round($d - $c, 2),
            ];
        }

        // Calculate section totals
        $sectionTotals = [];
        $flatLists = [];
        foreach ($types as $type) {
            $typeAccounts = $accounts->where('account_type', $type);
            $debit = 0;
            $credit = 0;
            $flat = [];

            foreach ($typeAccounts as $account) {
                $d = round((float) ($account->total_debit ?? 0), 2);
                $c = round((float) ($account->total_credit ?? 0), 2);
                $debit = bcadd($debit, $d, 2);
                $credit = bcadd($credit, $c, 2);
                $flat[] = [
                    'name' => $account->account_name,
                    'debit' => $d,
                    'credit' => $c,
                    'balance' => round($d - $c, 2),
                ];
            }

            $sectionTotals[$type] = ['debit' => $debit, 'credit' => $credit];
            $flatLists[$type] = $flat;
        }

        $totalAssets = bcsub($sectionTotals['asset']['debit'], $sectionTotals['asset']['credit'], 2);
        $totalLiabilities = bcsub($sectionTotals['liability']['credit'], $sectionTotals['liability']['debit'], 2);

        $totalDebit = bcadd(round(array_sum(array_column($sectionTotals, 'debit')), 2), $otherDebit, 2);
        $totalCredit = bcadd(round(array_sum(array_column($sectionTotals, 'credit')), 2), $otherCredit, 2);

        return [
            'assets' => $flatLists['asset'],
            'liabilities' => $flatLists['liability'],
            'equity' => $flatLists['equity'],
            'income' => $flatLists['income'],
            'expenses' => $flatLists['expense'],
            'other' => $otherFlat,
            'assetsTree' => $trees['asset'],
            'liabilitiesTree' => $trees['liability'],
            'equityTree' => $trees['equity'],
            'incomeTree' => $trees['income'],
            'expensesTree' => $trees['expense'],
            'otherTree' => [],
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'netBalance' => bcsub($totalAssets, $totalLiabilities, 2),
            'totalAssetsDebit' => $sectionTotals['asset']['debit'],
            'totalAssetsCredit' => $sectionTotals['asset']['credit'],
            'totalLiabilitiesDebit' => $sectionTotals['liability']['debit'],
            'totalLiabilitiesCredit' => $sectionTotals['liability']['credit'],
            'totalEquityDebit' => $sectionTotals['equity']['debit'],
            'totalEquityCredit' => $sectionTotals['equity']['credit'],
            'totalIncomeDebit' => $sectionTotals['income']['debit'],
            'totalIncomeCredit' => $sectionTotals['income']['credit'],
            'totalExpensesDebit' => $sectionTotals['expense']['debit'],
            'totalExpensesCredit' => $sectionTotals['expense']['credit'],
            'totalOtherDebit' => $otherDebit,
            'totalOtherCredit' => $otherCredit,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ];
    }

    protected function loadTrialBalance()
    {
        $data = $this->getTrialBalanceData();

        $this->assets = array_map(fn ($item) => (object) $item, $data['assets']);
        $this->liabilities = array_map(fn ($item) => (object) $item, $data['liabilities']);
        $this->equity = array_map(fn ($item) => (object) $item, $data['equity']);
        $this->income = array_map(fn ($item) => (object) $item, $data['income']);
        $this->expenses = array_map(fn ($item) => (object) $item, $data['expenses']);
        $this->other = array_map(fn ($item) => (object) $item, $data['other']);

        $this->assetsTree = $data['assetsTree'];
        $this->liabilitiesTree = $data['liabilitiesTree'];
        $this->equityTree = $data['equityTree'];
        $this->incomeTree = $data['incomeTree'];
        $this->expensesTree = $data['expensesTree'];
        $this->otherTree = $data['otherTree'];

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
        $this->totalOtherDebit = $data['totalOtherDebit'];
        $this->totalOtherCredit = $data['totalOtherCredit'];
        $this->totalDebit = $data['totalDebit'];
        $this->totalCredit = $data['totalCredit'];
    }

    protected function getBranchName(): ?string
    {
        if (! $this->branch_id) {
            return null;
        }

        return Branch::find($this->branch_id)?->name;
    }

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
     * Build hierarchical tree for an account type.
     * Structure: Master Category → Sub-Categories (Groups) → Accounts
     */
    protected function buildTreeStructure(string $accountType, $accounts): array
    {
        $typeAccounts = $accounts->where('account_type', $accountType);

        if ($typeAccounts->isEmpty()) {
            return [];
        }

        $categoryIds = $typeAccounts->pluck('account_category_id')->filter()->unique();

        if ($categoryIds->isEmpty()) {
            return ['uncategorized' => $this->mapAccountsToArray($typeAccounts)];
        }

        // Load categories + their parents for hierarchy
        $categories = AccountCategory::whereIn('id', $categoryIds)
            ->with(['parent', 'children'])
            ->get();

        $parentIds = $categories->pluck('parent_id')->filter()->unique();
        if ($parentIds->isNotEmpty()) {
            $parentCategories = AccountCategory::whereIn('id', $parentIds)
                ->whereNotIn('id', $categoryIds)
                ->with(['parent', 'children'])
                ->get();
            $categories = $categories->merge($parentCategories);
        }

        // Organize into masters and subs
        $categoryMap = [];
        $masterCategories = [];
        $subCategories = [];

        foreach ($categories as $category) {
            $categoryMap[$category->id] = $category;
            if ($category->parent_id) {
                $subCategories[$category->parent_id][] = $category;
            } else {
                $masterCategories[$category->id] = $category;
            }
        }

        $tree = [];
        $categorizedAccountIds = [];

        // Process each master category and its children
        $allMasters = collect($masterCategories);

        // Also include orphaned parent categories (sub-categories whose parent exists in categoryMap but not in masterCategories)
        foreach ($subCategories as $parentId => $subCats) {
            if (! isset($masterCategories[$parentId]) && isset($categoryMap[$parentId])) {
                $allMasters[$parentId] = $categoryMap[$parentId];
            }
        }

        foreach ($allMasters->sortBy('name') as $masterId => $masterCategory) {
            $result = $this->buildMasterCategoryNode($masterCategory, $typeAccounts, $subCategories[$masterId] ?? [], $categorizedAccountIds);
            if ($result) {
                $tree[] = $result;
            }
        }

        // Uncategorized accounts
        $uncategorized = [];
        foreach ($typeAccounts as $account) {
            if (! in_array($account->account_id, $categorizedAccountIds)) {
                $uncategorized[] = $this->mapSingleAccount($account);
            }
        }

        if (! empty($uncategorized)) {
            $tree['uncategorized'] = $uncategorized;
        }

        return $tree;
    }

    /**
     * Build a single master category node with its groups and direct accounts.
     */
    private function buildMasterCategoryNode($masterCategory, $typeAccounts, array $subCats, array &$categorizedAccountIds): ?array
    {
        $masterDebit = 0;
        $masterCredit = 0;
        $directAccounts = [];
        $groups = [];

        // Direct accounts under this master category
        foreach ($typeAccounts->where('account_category_id', $masterCategory->id) as $account) {
            $mapped = $this->mapSingleAccount($account);
            $masterDebit += $mapped['debit'];
            $masterCredit += $mapped['credit'];
            $categorizedAccountIds[] = $account->account_id;
            $directAccounts[] = $mapped;
        }

        // Sub-categories (groups)
        foreach (collect($subCats)->sortBy('name') as $subCategory) {
            $groupDebit = 0;
            $groupCredit = 0;
            $groupAccounts = [];

            foreach ($typeAccounts->where('account_category_id', $subCategory->id) as $account) {
                $mapped = $this->mapSingleAccount($account);
                $groupDebit += $mapped['debit'];
                $groupCredit += $mapped['credit'];
                $masterDebit += $mapped['debit'];
                $masterCredit += $mapped['credit'];
                $categorizedAccountIds[] = $account->account_id;
                $groupAccounts[] = $mapped;
            }

            if (! empty($groupAccounts)) {
                $groups[] = [
                    'id' => $subCategory->id,
                    'name' => $subCategory->name,
                    'debit' => $groupDebit,
                    'credit' => $groupCredit,
                    'balance' => round($groupDebit - $groupCredit, 2),
                    'accounts' => $groupAccounts,
                ];
            }
        }

        if (empty($directAccounts) && empty($groups)) {
            return null;
        }

        return [
            'id' => $masterCategory->id,
            'name' => $masterCategory->name,
            'debit' => $masterDebit,
            'credit' => $masterCredit,
            'balance' => round($masterDebit - $masterCredit, 2),
            'groups' => $groups,
            'directAccounts' => $directAccounts,
        ];
    }

    private function mapSingleAccount($account): array
    {
        $debit = round((float) ($account->total_debit ?? 0), 2);
        $credit = round((float) ($account->total_credit ?? 0), 2);

        return [
            'id' => $account->account_id,
            'name' => $account->account_name,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => round($debit - $credit, 2),
        ];
    }

    private function mapAccountsToArray($accounts): array
    {
        return $accounts->map(fn ($account) => $this->mapSingleAccount($account))->values()->toArray();
    }

    public function render()
    {
        return view('livewire.reports.trial-balance');
    }
}
