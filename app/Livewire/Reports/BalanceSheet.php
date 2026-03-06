<?php

namespace App\Livewire\Reports;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Livewire\Component;

class BalanceSheet extends Component
{
    public $branch_id = '';

    public $period = 'monthly';

    public $end_date;

    public $branches = [];

    public $hideCustomers = true;

    public $hideVendors = true;

    // Account categories - grouped by account heads
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
        $this->branch_id = session('branch_id');
        $this->period = Carbon::now()->format('Y-m');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadBalanceSheet();
    }

    public function updatedPeriod($value)
    {
        switch ($value) {
            case 'monthly':
                $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarterly':
                $this->end_date = Carbon::now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'yearly':
                $this->end_date = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
            case 'previous_month':
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

    public function updatedHideCustomers()
    {
        $this->loadBalanceSheet();
    }

    public function updatedHideVendors()
    {
        $this->loadBalanceSheet();
    }

    public function loadBalanceSheet()
    {
        // Reset all arrays and totals
        $this->resetData();

        // Validate end_date
        if (empty($this->end_date)) {
            $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // Use single aggregated query instead of N+1 queries
        // Get all account balances in one query (entries up to and including end date)
        $accountBalances = JournalEntry::query()
            ->selectRaw('
                journal_entries.account_id,
                accounts.id,
                accounts.name,
                accounts.account_type,
                accounts.account_category_id,
                accounts.model,
                COALESCE(SUM(journal_entries.debit), 0) as total_debit,
                COALESCE(SUM(journal_entries.credit), 0) as total_credit
            ')
            ->join('accounts', 'accounts.id', '=', 'journal_entries.account_id')
            ->where('journal_entries.date', '<=', $this->end_date)
            ->when($this->branch_id, function ($q) {
                return $q->where('journal_entries.branch_id', $this->branch_id);
            })
            ->whereNull('journal_entries.deleted_at')
            ->whereNull('accounts.deleted_at')
            ->groupBy('journal_entries.account_id', 'accounts.id', 'accounts.name', 'accounts.account_type', 'accounts.account_category_id', 'accounts.model')
            ->get();

        // Load all accounts with categories in one query
        $accountIds = $accountBalances->pluck('account_id')->unique();
        $accounts = Account::with('accountCategory')
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        // Build accounts with balances array
        // Note: We include all accounts in calculations for accurate totals
        // But we'll filter them from display in buildTreeStructure if excluded
        $accountsWithBalances = [];
        foreach ($accountBalances as $balanceData) {
            $account = $accounts->get($balanceData->account_id);

            if (! $account) {
                continue;
            }

            $totalDebit = (float) ($balanceData->total_debit ?? 0);
            $totalCredit = (float) ($balanceData->total_credit ?? 0);

            // Calculate balance based on account type
            $balance = $this->calculateAccountBalance($account, $totalDebit, $totalCredit);

            // Only include accounts with non-zero balance (using proper precision)
            if (abs($balance) >= 0.01) {
                $accountsWithBalances[] = [
                    'account' => $account,
                    'balance' => $balance,
                ];
            }
        }

        // Group accounts by type and category
        $this->groupAccountsByCategory($accountsWithBalances);

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

    private function groupAccountsByCategory($accountsWithBalances)
    {
        // Group by account type
        $groupedByType = [];
        foreach ($accountsWithBalances as $item) {
            $account = $item['account'];
            $balance = $item['balance'];
            $accountType = $account->account_type;
            if (! isset($groupedByType[$accountType])) {
                $groupedByType[$accountType] = [];
            }

            $groupedByType[$accountType][] = [
                'account' => $account,
                'balance' => $balance,
            ];
        }

        // Process each account type
        foreach ($groupedByType as $accountType => $accounts) {
            $structure = $this->buildTreeStructure($accountType, $accounts);

            switch ($accountType) {
                case 'asset':
                    // Separate into current, fixed, and other assets
                    foreach ($structure as $category) {
                        $categoryTotal = round((float) ($category['total'] ?? 0), 2);
                        $categoryName = strtolower($category['name'] ?? '');
                        if (stripos($categoryName, 'fixed') !== false) {
                            $this->fixedAssets[] = $category;
                            $this->totalFixedAssets += $categoryTotal;
                        } elseif (! empty($category['groups']) || ! empty($category['directAccounts'])) {
                            $this->currentAssets[] = $category;
                            $this->totalCurrentAssets += $categoryTotal;
                        } else {
                            $this->otherAssets[] = $category;
                            $this->totalOtherAssets += $categoryTotal;
                        }
                    }
                    break;

                case 'liability':
                    // Separate into current and long-term liabilities
                    foreach ($structure as $category) {
                        $categoryTotal = round((float) ($category['total'] ?? 0), 2);
                        $categoryName = strtolower($category['name'] ?? '');
                        if (stripos($categoryName, 'long') !== false) {
                            $this->longTermLiabilities[] = $category;
                            $this->totalLongTermLiabilities += $categoryTotal;
                        } else {
                            $this->currentLiabilities[] = $category;
                            $this->totalCurrentLiabilities += $categoryTotal;
                        }
                    }
                    break;

                case 'equity':
                    $this->ownerEquity = $structure;
                    foreach ($structure as $category) {
                        $this->totalEquityAccounts += round((float) ($category['total'] ?? 0), 2);
                    }
                    break;

                case 'income':
                case 'expense':
                    // Income and expenses go to retained earnings
                    $this->retainedEarningAccounts = array_merge($this->retainedEarningAccounts, $structure);
                    foreach ($structure as $category) {
                        $categoryTotal = round((float) ($category['total'] ?? 0), 2);
                        if ($accountType === 'income') {
                            $this->totalRetainedEarnings += $categoryTotal;
                        } else {
                            $this->totalRetainedEarnings -= $categoryTotal;
                        }
                    }
                    break;

                default:
                    $this->otherAssets = array_merge($this->otherAssets, $structure);
                    foreach ($structure as $category) {
                        $this->totalOtherAssets += round((float) ($category['total'] ?? 0), 2);
                    }
                    break;
            }
        }
    }

    /**
     * Build tree structure for account type with categories
     * Optimized version with better performance and simpler logic
     */
    private function buildTreeStructure(string $accountType, array $accountsWithBalances)
    {
        if (empty($accountsWithBalances)) {
            return [];
        }

        // Get all unique category IDs from accounts
        $categoryIds = collect($accountsWithBalances)
            ->pluck('account.account_category_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // If no categories, return flat list as unCategorized
        if (empty($categoryIds)) {
            return $this->buildUnCategorizedTree($accountsWithBalances);
        }

        // Load all categories with their hierarchy in one query
        $categories = AccountCategory::whereIn('id', $categoryIds)
            ->with(['parent', 'children'])
            ->get();

        // Also load any missing parent categories
        $parentIds = $categories->pluck('parent_id')->filter()->unique()->diff($categories->pluck('id'));
        if ($parentIds->isNotEmpty()) {
            $missingParents = AccountCategory::whereIn('id', $parentIds)->get();
            $categories = $categories->merge($missingParents);
        }

        // Build category maps for efficient lookup
        $categoryMap = $categories->keyBy('id');
        $masterCategories = $categories->whereNull('parent_id')->keyBy('id');
        $subCategoriesByParent = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        // Group accounts by category for efficient processing
        $accountsByCategory = [];
        $accountsBySubCategory = [];
        $processedAccountIds = [];

        foreach ($accountsWithBalances as $item) {
            $account = $item['account'];
            $balance = $item['balance'];
            $categoryId = $account->account_category_id;

            if (! $categoryId || ! isset($categoryMap[$categoryId])) {
                continue;
            }

            $category = $categoryMap[$categoryId];
            $processedAccountIds[] = $account->id;

            // Check if account should be hidden from display
            $isCustomer = strtolower($account->model ?? '') === 'customer';
            $isVendor = strtolower($account->model ?? '') === 'vendor';
            $shouldHide = ($this->hideCustomers && $isCustomer) || ($this->hideVendors && $isVendor);

            if ($category->parent_id) {
                // Sub-category account
                if (! isset($accountsBySubCategory[$categoryId])) {
                    $accountsBySubCategory[$categoryId] = [
                        'category' => $category,
                        'accounts' => [],
                        'total' => 0,
                    ];
                }
                // Always include in total, but only add to accounts array if not hidden
                if (! $shouldHide) {
                    $accountsBySubCategory[$categoryId]['accounts'][] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'amount' => abs($balance),
                    ];
                }
                $accountsBySubCategory[$categoryId]['total'] += $balance;
            } else {
                // Master category account
                if (! isset($accountsByCategory[$categoryId])) {
                    $accountsByCategory[$categoryId] = [
                        'category' => $category,
                        'accounts' => [],
                        'total' => 0,
                    ];
                }
                // Always include in total, but only add to accounts array if not hidden
                if (! $shouldHide) {
                    $accountsByCategory[$categoryId]['accounts'][] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'amount' => abs($balance),
                    ];
                }
                $accountsByCategory[$categoryId]['total'] += $balance;
            }
        }

        // Build tree structure
        $tree = [];
        foreach ($masterCategories as $masterCategory) {
            $masterId = $masterCategory->id;
            $masterTotal = $accountsByCategory[$masterId]['total'] ?? 0;
            $directAccounts = $accountsByCategory[$masterId]['accounts'] ?? [];
            $groups = [];

            // Process sub-categories for this master
            if (isset($subCategoriesByParent[$masterId])) {
                foreach ($subCategoriesByParent[$masterId] as $subCategory) {
                    $subCategoryId = $subCategory->id;
                    if (isset($accountsBySubCategory[$subCategoryId])) {
                        $subData = $accountsBySubCategory[$subCategoryId];
                        $masterTotal += $subData['total'];
                        // Filter accounts array based on exclusion settings
                        $filteredAccounts = [];
                        foreach ($subData['accounts'] as $acc) {
                            // Accounts are already filtered in the loop above, so just add them
                            $filteredAccounts[] = $acc;
                        }
                        // Show sub-category if it has a total, even if no accounts are visible
                        if (abs($subData['total']) >= 0.01) {
                            $groups[] = [
                                'id' => $subCategoryId,
                                'name' => $subCategory->name,
                                'total' => round($subData['total'], 2),
                                'accounts' => $filteredAccounts,
                            ];
                        }
                    }
                }
            }

            // Show category if it has a total, even if no accounts are visible
            if (abs($masterTotal) >= 0.01) {
                $tree[] = [
                    'id' => $masterId,
                    'name' => $masterCategory->name,
                    'total' => round($masterTotal, 2),
                    'groups' => $groups,
                    'directAccounts' => $directAccounts,
                ];
            }
        }

        // Handle uncategorized accounts
        $uncategorized = [];
        $uncategorizedTotal = 0;
        foreach ($accountsWithBalances as $item) {
            if (! in_array($item['account']->id, $processedAccountIds)) {
                $isCustomer = strtolower($item['account']->model ?? '') === 'customer';
                $isVendor = strtolower($item['account']->model ?? '') === 'vendor';
                $shouldHide = ($this->hideCustomers && $isCustomer) || ($this->hideVendors && $isVendor);

                // Always include in total
                $uncategorizedTotal += $item['balance'];

                // Only add to display array if not hidden
                if (! $shouldHide) {
                    $uncategorized[] = [
                        'id' => $item['account']->id,
                        'name' => $item['account']->name,
                        'amount' => abs($item['balance']),
                    ];
                }
            }
        }

        if (! empty($uncategorized) || $uncategorizedTotal != 0) {
            $uncategorizedTotal = round($uncategorizedTotal, 2);

            $tree[] = [
                'id' => 0,
                'name' => 'Un Categorized',
                'total' => $uncategorizedTotal,
                'groups' => [],
                'directAccounts' => $uncategorized,
            ];
        }

        return $tree;
    }

    /**
     * Build tree structure for uncategorized accounts
     */
    private function buildUnCategorizedTree(array $accountsWithBalances)
    {
        $uncategorized = [];
        $total = 0;

        foreach ($accountsWithBalances as $item) {
            $account = $item['account'];
            $balance = $item['balance'];
            $total += $balance;

            // Check if account should be hidden from display
            $isCustomer = strtolower($account->model ?? '') === 'customer';
            $isVendor = strtolower($account->model ?? '') === 'vendor';
            $shouldHide = ($this->hideCustomers && $isCustomer) || ($this->hideVendors && $isVendor);

            // Only add to display array if not hidden
            if (! $shouldHide) {
                $uncategorized[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'amount' => abs($balance),
                ];
            }
        }

        if (empty($uncategorized) && $total == 0) {
            return [];
        }

        return [[
            'id' => 0,
            'name' => 'UnCategorized',
            'total' => round($total, 2),
            'groups' => [],
            'directAccounts' => $uncategorized,
        ]];
    }

    private function calculateTotals()
    {
        // Calculate asset totals with proper precision
        $this->totalCurrentAssets = round($this->totalCurrentAssets, 2);
        $this->totalFixedAssets = round($this->totalFixedAssets, 2);
        $this->totalOtherAssets = round($this->totalOtherAssets, 2);
        $this->totalAssets = round($this->totalCurrentAssets + $this->totalFixedAssets + $this->totalOtherAssets, 2);

        // Calculate liability totals with proper precision
        $this->totalCurrentLiabilities = round($this->totalCurrentLiabilities, 2);
        $this->totalLongTermLiabilities = round($this->totalLongTermLiabilities, 2);
        $this->totalLiabilities = round($this->totalCurrentLiabilities + $this->totalLongTermLiabilities, 2);

        // Calculate equity totals with proper precision (owner's equity + retained earnings)
        $this->totalEquityAccounts = round($this->totalEquityAccounts, 2);
        $this->totalRetainedEarnings = round($this->totalRetainedEarnings, 2);
        $this->totalEquity = round($this->totalEquityAccounts + $this->totalRetainedEarnings, 2);
    }

    private function calculateAccountBalance($account, $totalDebit, $totalCredit)
    {
        // Ensure inputs are properly cast to float
        $debit = (float) $totalDebit;
        $credit = (float) $totalCredit;

        // Calculate balance based on account type and normal balance
        switch ($account->account_type) {
            case 'asset':
            case 'expense':
                // Debit balance normal (debit - credit)
                return round($debit - $credit, 2);

            case 'liability':
            case 'equity':
            case 'income':
                // Credit balance normal (credit - debit)
                return round($credit - $debit, 2);

            default:
                return 0.0;
        }
    }

    public function render()
    {
        return view('livewire.reports.balance-sheet');
    }
}
