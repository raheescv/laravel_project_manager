<?php

namespace App\Livewire\Reports;

use App\Models\AccountCategory;
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

        // Reset arrays and totals
        $this->assets = [];
        $this->liabilities = [];
        $this->equity = [];
        $this->income = [];
        $this->expenses = [];

        $this->assetsTree = [];
        $this->liabilitiesTree = [];
        $this->equityTree = [];
        $this->incomeTree = [];
        $this->expensesTree = [];

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

        // Build tree structures for each account type
        $this->assetsTree = $this->buildTreeStructure('asset', $accounts);
        $this->liabilitiesTree = $this->buildTreeStructure('liability', $accounts);
        $this->equityTree = $this->buildTreeStructure('equity', $accounts);
        $this->incomeTree = $this->buildTreeStructure('income', $accounts);
        $this->expensesTree = $this->buildTreeStructure('expense', $accounts);

        // Process and categorize accounts (keep for backward compatibility)
        foreach ($accounts as $account) {
            $debit = round((float) ($account->total_debit ?? 0), 2);
            $credit = round((float) ($account->total_credit ?? 0), 2);

            $accountData = (object) [
                'code' => $account->account_code ?? null,
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

    /**
     * Build tree structure for account type with categories
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
                $uncategorized[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            }
            return ['uncategorized' => $uncategorized];
        }

        // Get categories with their hierarchy
        $categories = AccountCategory::whereIn('id', $categoryIds)
            ->with(['parent', 'children'])
            ->get();

        // Build category map
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

        // Build tree structure
        $tree = [];
        
        // Process master categories
        foreach ($masterCategories as $masterId => $masterCategory) {
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
                
                $directAccounts[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            }

            // Process sub-categories (groups)
            if (isset($subCategories[$masterId])) {
                foreach ($subCategories[$masterId] as $subCategory) {
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
                        
                        $groupAccounts[] = [
                            'id' => $account->account_id,
                            'name' => $account->account_name,
                            'debit' => $debit,
                            'credit' => $credit,
                        ];
                    }

                    if (!empty($groupAccounts)) {
                        $groups[] = [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                            'debit' => $groupDebit,
                            'credit' => $groupCredit,
                            'accounts' => $groupAccounts,
                        ];
                    }
                }
            }

            if (!empty($directAccounts) || !empty($groups)) {
                $tree[] = [
                    'id' => $masterCategory->id,
                    'name' => $masterCategory->name,
                    'debit' => $masterDebit,
                    'credit' => $masterCredit,
                    'groups' => $groups,
                    'directAccounts' => $directAccounts,
                ];
            }
        }

        // Handle uncategorized accounts (accounts without category_id or with invalid category_id)
        $uncategorized = [];
        $categorizedAccountIds = [];
        
        // Collect all account IDs that are in categories
        foreach ($tree as $item) {
            if (isset($item['directAccounts'])) {
                foreach ($item['directAccounts'] as $acc) {
                    $categorizedAccountIds[] = $acc['id'];
                }
            }
            if (isset($item['groups'])) {
                foreach ($item['groups'] as $group) {
                    if (isset($group['accounts'])) {
                        foreach ($group['accounts'] as $acc) {
                            $categorizedAccountIds[] = $acc['id'];
                        }
                    }
                }
            }
        }
        
        // Find accounts that are not in any category or have invalid category
        foreach ($typeAccounts as $account) {
            if (!in_array($account->account_id, $categorizedAccountIds)) {
                $debit = round((float) ($account->total_debit ?? 0), 2);
                $credit = round((float) ($account->total_credit ?? 0), 2);
                
                $uncategorized[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            }
        }

        if (!empty($uncategorized)) {
            $tree['uncategorized'] = $uncategorized;
        }

        return $tree;
    }

    public function render()
    {
        return view('livewire.reports.trial-balance');
    }
}
