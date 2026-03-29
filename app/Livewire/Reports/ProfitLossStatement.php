<?php

namespace App\Livewire\Reports;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Livewire\Component;

class ProfitLossStatement extends Component
{
    public $branch_id = '';

    public $period = 'monthly';

    public $start_date;

    public $end_date;

    public $incomeTree = [];

    public $expenseTree = [];

    public $otherTree = [];

    public $totalIncome = 0;

    public $totalExpense = 0;

    public $totalOther = 0;

    public $netProfit = 0;

    public function mount()
    {
        $this->branch_id = session('branch_id');
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
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
    }

    public function updatedStartDate() {}

    public function updatedEndDate() {}

    public function updatedBranchId() {}

    /**
     * Get the amount for an account in the period.
     */
    private function getAccountAmount(int $accountId, string $type): float
    {
        $query = JournalEntry::query()
            ->where('account_id', $accountId)
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id));

        $result = $query->first();

        if (! $result) {
            return 0.0;
        }

        // Income: credit - debit, Expense: debit - credit
        $amount = $type === 'income'
            ? (float) $result->total_credit - (float) $result->total_debit
            : (float) $result->total_debit - (float) $result->total_credit;

        return max(0, round($amount, 2));
    }

    /**
     * Build tree structure for income or expense categories.
     * Uses category names (Direct Income, Indirect Income, Direct Expense, Indirect Expense)
     * to find accounts — same approach as the existing ProfitLoss component.
     */
    private function buildCategoryTree(string $type): array
    {
        $categoryNames = $type === 'income'
            ? ['Direct Income', 'Indirect Income']
            : ['Direct Expense', 'Indirect Expense'];

        $tree = [];

        // Special accounts to exclude (handled separately in P&L T-account format)
        $specialNames = ['Sale', 'Purchase', 'Purchase Returns', 'Sales Returns'];

        foreach ($categoryNames as $catName) {
            $master = AccountCategory::where('name', $catName)
                ->whereNull('parent_id')
                ->with(['children.accounts' => fn ($q) => $q->where('account_type', $type),
                    'accounts' => fn ($q) => $q->where('account_type', $type)])
                ->first();

            if (! $master) {
                continue;
            }

            $masterTotal = 0;
            $directAccounts = [];
            $groups = [];

            // Direct accounts under master category
            foreach ($master->accounts as $account) {
                if (in_array($account->name, $specialNames)) {
                    continue;
                }
                $amount = $this->getAccountAmount($account->id, $type);
                $masterTotal += $amount;
                $directAccounts[] = ['id' => $account->id, 'name' => $account->name, 'amount' => $amount];
            }

            // Sub-categories (groups)
            foreach ($master->children as $group) {
                $groupTotal = 0;
                $groupAccounts = [];

                foreach ($group->accounts as $account) {
                    if (in_array($account->name, $specialNames)) {
                        continue;
                    }
                    $amount = $this->getAccountAmount($account->id, $type);
                    $groupTotal += $amount;
                    $groupAccounts[] = ['id' => $account->id, 'name' => $account->name, 'amount' => $amount];
                }

                $masterTotal += $groupTotal;

                if (! empty($groupAccounts)) {
                    $groups[] = [
                        'id' => $group->id,
                        'name' => $group->name,
                        'amount' => round($groupTotal, 2),
                        'accounts' => $groupAccounts,
                    ];
                }
            }

            if (! empty($directAccounts) || ! empty($groups)) {
                $tree[] = [
                    'id' => $master->id,
                    'name' => $master->name,
                    'amount' => round($masterTotal, 2),
                    'groups' => $groups,
                    'directAccounts' => $directAccounts,
                ];
            }
        }

        // Uncategorized accounts of this type
        $uncategorized = Account::where('account_type', $type)
            ->whereNull('account_category_id')
            ->whereNotIn('name', $specialNames)
            ->get();

        $uncatAccounts = [];
        $uncatTotal = 0;

        foreach ($uncategorized as $account) {
            $amount = $this->getAccountAmount($account->id, $type);
            if ($amount > 0) {
                $uncatTotal += $amount;
                $uncatAccounts[] = ['id' => $account->id, 'name' => $account->name, 'amount' => $amount];
            }
        }

        if (! empty($uncatAccounts)) {
            $tree['uncategorized'] = $uncatAccounts;
        }

        return $tree;
    }

    private function sumTree(array $tree): float
    {
        $total = 0;
        foreach ($tree as $index => $item) {
            if ($index === 'uncategorized' && is_array($item)) {
                foreach ($item as $account) {
                    $total += $account['amount'];
                }
            } elseif (isset($item['amount'])) {
                $total += $item['amount'];
            }
        }

        return round($total, 2);
    }

    /**
     * Build "Other" tree for accounts with no account_type that have transactions.
     * These are unclassified accounts that should be reviewed.
     */
    private function buildOtherTree(): array
    {
        $accounts = JournalEntry::query()
            ->selectRaw('
                account_id,
                accounts.name as account_name,
                COALESCE(SUM(journal_entries.debit), 0) as total_debit,
                COALESCE(SUM(journal_entries.credit), 0) as total_credit
            ')
            ->join('accounts', 'accounts.id', '=', 'account_id')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->where(function ($q) {
                $q->whereNull('accounts.account_type')->orWhere('accounts.account_type', '');
            })
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->groupBy('account_id', 'accounts.name')
            ->get();

        $items = [];
        foreach ($accounts as $account) {
            $d = round((float) $account->total_debit, 2);
            $c = round((float) $account->total_credit, 2);
            $net = round($d - $c, 2);
            if (abs($net) >= 0.01) {
                $items[] = [
                    'id' => $account->account_id,
                    'name' => $account->account_name,
                    'amount' => $net,
                ];
            }
        }

        return $items;
    }

    public function render()
    {
        $this->incomeTree = $this->buildCategoryTree('income');
        $this->expenseTree = $this->buildCategoryTree('expense');
        $this->otherTree = $this->buildOtherTree();
        $this->totalIncome = $this->sumTree($this->incomeTree);
        $this->totalExpense = $this->sumTree($this->expenseTree);
        $this->totalOther = round(collect($this->otherTree)->sum('amount'), 2);
        $this->netProfit = round($this->totalIncome - $this->totalExpense - $this->totalOther, 2);

        return view('livewire.reports.profit-loss-statement');
    }
}
