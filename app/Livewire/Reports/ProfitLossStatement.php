<?php

namespace App\Livewire\Reports;

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

    public function updatedStartDate()
    {
        // triggers re-render
    }

    public function updatedEndDate()
    {
        // triggers re-render
    }

    public function updatedBranchId()
    {
        // triggers re-render
    }

    /**
     * IMPORTANT: Single query fetches ALL account balances for the period.
     * This replaces the old N+1 getAccountAmount() approach that caused memory exhaustion.
     * DO NOT replace this with per-account queries.
     */
    private function fetchAllBalances()
    {
        return JournalEntry::query()
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
            ->groupBy('account_id', 'accounts.name', 'accounts.account_type', 'accounts.account_category_id')
            ->get();
    }

    /**
     * Build tree for income or expense using the pre-fetched balances collection.
     * No additional DB queries inside this method.
     */
    private function buildTreeFromBalances(string $type, $balances): array
    {
        $categoryNames = $type === 'income'
            ? ['Direct Income', 'Indirect Income']
            : ['Direct Expense', 'Indirect Expense'];

        $typeBalances = $balances->where('account_type', $type)->keyBy('account_id');

        if ($typeBalances->isEmpty()) {
            return [];
        }

        $masters = AccountCategory::whereIn('name', $categoryNames)
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        $tree = [];
        $usedIds = [];

        foreach ($masters as $master) {
            $masterTotal = 0;
            $directAccounts = [];
            $groups = [];

            foreach ($typeBalances as $bal) {
                if ($bal->account_category_id == $master->id) {
                    $amt = $this->calcAmt($bal, $type);
                    if ($amt > 0) {
                        $masterTotal += $amt;
                        $directAccounts[] = ['id' => $bal->account_id, 'name' => $bal->account_name, 'amount' => $amt];
                        $usedIds[] = $bal->account_id;
                    }
                }
            }

            foreach ($master->children->sortBy('name') as $child) {
                $grpTotal = 0;
                $grpAccounts = [];

                foreach ($typeBalances as $bal) {
                    if ($bal->account_category_id == $child->id) {
                        $amt = $this->calcAmt($bal, $type);
                        if ($amt > 0) {
                            $grpTotal += $amt;
                            $grpAccounts[] = ['id' => $bal->account_id, 'name' => $bal->account_name, 'amount' => $amt];
                            $usedIds[] = $bal->account_id;
                        }
                    }
                }

                if (! empty($grpAccounts)) {
                    $masterTotal += $grpTotal;
                    $groups[] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'amount' => round($grpTotal, 2),
                        'accounts' => $grpAccounts,
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

        // Uncategorized typed accounts
        $uncatAccounts = [];
        foreach ($typeBalances as $bal) {
            if (! in_array($bal->account_id, $usedIds)) {
                $amt = $this->calcAmt($bal, $type);
                if ($amt > 0) {
                    $uncatAccounts[] = ['id' => $bal->account_id, 'name' => $bal->account_name, 'amount' => $amt];
                }
            }
        }

        if (! empty($uncatAccounts)) {
            $tree['uncategorized'] = $uncatAccounts;
        }

        return $tree;
    }

    /**
     * Build "Other" list for untyped accounts from the pre-fetched balances.
     */
    private function buildOtherFromBalances($balances): array
    {
        $items = [];
        foreach ($balances as $bal) {
            if (! empty($bal->account_type)) {
                continue;
            }
            $d = round((float) $bal->total_debit, 2);
            $c = round((float) $bal->total_credit, 2);
            $net = round($d - $c, 2);
            if (abs($net) >= 0.01) {
                $items[] = ['id' => $bal->account_id, 'name' => $bal->account_name, 'amount' => $net];
            }
        }

        return $items;
    }

    private function calcAmt($balance, string $type): float
    {
        $d = round((float) $balance->total_debit, 2);
        $c = round((float) $balance->total_credit, 2);

        return max(0, round($type === 'income' ? $c - $d : $d - $c, 2));
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

    public function render()
    {
        // IMPORTANT: One single DB query fetches all balances, then trees are built in-memory.
        $balances = $this->fetchAllBalances();

        $this->incomeTree = $this->buildTreeFromBalances('income', $balances);
        $this->expenseTree = $this->buildTreeFromBalances('expense', $balances);
        $this->otherTree = $this->buildOtherFromBalances($balances);
        $this->totalIncome = $this->sumTree($this->incomeTree);
        $this->totalExpense = $this->sumTree($this->expenseTree);
        $this->totalOther = round(collect($this->otherTree)->sum('amount'), 2);
        $this->netProfit = round($this->totalIncome - $this->totalExpense - $this->totalOther, 2);

        return view('livewire.reports.profit-loss-statement');
    }
}
