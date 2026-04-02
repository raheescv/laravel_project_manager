<?php

namespace App\Livewire\Reports;

use App\Exports\ProfitLossStatementExport;
use App\Models\AccountCategory;
use App\Models\Branch;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

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

    // Section debit/credit totals
    public $totalIncomeDebit = 0;

    public $totalIncomeCredit = 0;

    public $totalExpenseDebit = 0;

    public $totalExpenseCredit = 0;

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
     * Now includes debit, credit, and balance per account/group/category.
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
            $masterDebit = 0;
            $masterCredit = 0;
            $directAccounts = [];
            $groups = [];

            foreach ($typeBalances as $bal) {
                if ($bal->account_category_id == $master->id) {
                    $d = round((float) $bal->total_debit, 2);
                    $c = round((float) $bal->total_credit, 2);
                    $amt = $this->calcAmt($bal, $type);
                    if ($d > 0 || $c > 0) {
                        $masterDebit += $d;
                        $masterCredit += $c;
                        $directAccounts[] = [
                            'id' => $bal->account_id,
                            'name' => $bal->account_name,
                            'debit' => $d,
                            'credit' => $c,
                            'balance' => round($d - $c, 2),
                            'amount' => $amt,
                        ];
                        $usedIds[] = $bal->account_id;
                    }
                }
            }

            foreach ($master->children->sortBy('name') as $child) {
                $grpDebit = 0;
                $grpCredit = 0;
                $grpAccounts = [];

                foreach ($typeBalances as $bal) {
                    if ($bal->account_category_id == $child->id) {
                        $d = round((float) $bal->total_debit, 2);
                        $c = round((float) $bal->total_credit, 2);
                        $amt = $this->calcAmt($bal, $type);
                        if ($d > 0 || $c > 0) {
                            $grpDebit += $d;
                            $grpCredit += $c;
                            $grpAccounts[] = [
                                'id' => $bal->account_id,
                                'name' => $bal->account_name,
                                'debit' => $d,
                                'credit' => $c,
                                'balance' => round($d - $c, 2),
                                'amount' => $amt,
                            ];
                            $usedIds[] = $bal->account_id;
                        }
                    }
                }

                if (! empty($grpAccounts)) {
                    $masterDebit += $grpDebit;
                    $masterCredit += $grpCredit;
                    $groups[] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'debit' => round($grpDebit, 2),
                        'credit' => round($grpCredit, 2),
                        'balance' => round($grpDebit - $grpCredit, 2),
                        'amount' => round($type === 'income' ? $grpCredit - $grpDebit : $grpDebit - $grpCredit, 2),
                        'accounts' => $grpAccounts,
                    ];
                }
            }

            if (! empty($directAccounts) || ! empty($groups)) {
                $tree[] = [
                    'id' => $master->id,
                    'name' => $master->name,
                    'debit' => round($masterDebit, 2),
                    'credit' => round($masterCredit, 2),
                    'balance' => round($masterDebit - $masterCredit, 2),
                    'amount' => round($type === 'income' ? $masterCredit - $masterDebit : $masterDebit - $masterCredit, 2),
                    'groups' => $groups,
                    'directAccounts' => $directAccounts,
                ];
            }
        }

        // Uncategorized typed accounts
        $uncatAccounts = [];
        foreach ($typeBalances as $bal) {
            if (! in_array($bal->account_id, $usedIds)) {
                $d = round((float) $bal->total_debit, 2);
                $c = round((float) $bal->total_credit, 2);
                $amt = $this->calcAmt($bal, $type);
                if ($d > 0 || $c > 0) {
                    $uncatAccounts[] = [
                        'id' => $bal->account_id,
                        'name' => $bal->account_name,
                        'debit' => $d,
                        'credit' => $c,
                        'balance' => round($d - $c, 2),
                        'amount' => $amt,
                    ];
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
            if ($d > 0 || $c > 0) {
                $items[] = [
                    'id' => $bal->account_id,
                    'name' => $bal->account_name,
                    'debit' => $d,
                    'credit' => $c,
                    'balance' => $net,
                    'amount' => $net,
                ];
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

    private function sumTreeDebitCredit(array $tree): array
    {
        $debit = 0;
        $credit = 0;
        foreach ($tree as $index => $item) {
            if ($index === 'uncategorized' && is_array($item)) {
                foreach ($item as $account) {
                    $debit += $account['debit'];
                    $credit += $account['credit'];
                }
            } elseif (isset($item['debit'])) {
                $debit += $item['debit'];
                $credit += $item['credit'];
            }
        }

        return ['debit' => round($debit, 2), 'credit' => round($credit, 2)];
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
            // Build the data fresh for export
            $balances = $this->fetchAllBalances();
            $incomeTree = $this->buildTreeFromBalances('income', $balances);
            $expenseTree = $this->buildTreeFromBalances('expense', $balances);
            $otherTree = $this->buildOtherFromBalances($balances);

            $incomeTotals = $this->sumTreeDebitCredit($incomeTree);
            $expenseTotals = $this->sumTreeDebitCredit($expenseTree);

            $reportData = [
                'incomeTree' => $incomeTree,
                'expenseTree' => $expenseTree,
                'otherTree' => $otherTree,
                'totalIncomeDebit' => $incomeTotals['debit'],
                'totalIncomeCredit' => $incomeTotals['credit'],
                'totalExpenseDebit' => $expenseTotals['debit'],
                'totalExpenseCredit' => $expenseTotals['credit'],
                'totalOtherDebit' => round(collect($otherTree)->sum('debit'), 2),
                'totalOtherCredit' => round(collect($otherTree)->sum('credit'), 2),
                'totalDebit' => round($incomeTotals['debit'] + $expenseTotals['debit'] + collect($otherTree)->sum('debit'), 2),
                'totalCredit' => round($incomeTotals['credit'] + $expenseTotals['credit'] + collect($otherTree)->sum('credit'), 2),
                'netProfit' => $this->netProfit,
            ];

            $branchName = $this->getBranchName();
            $fileName = 'Profit_Loss_Statement_'.$this->start_date.'_to_'.$this->end_date.'_'.now()->format('Y-m-d_H-i-s').'.xlsx';

            return Excel::download(
                new ProfitLossStatementExport($reportData, $this->start_date, $this->end_date, $branchName),
                $fileName
            );
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
        }
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

        // Calculate section debit/credit totals
        $incomeTotals = $this->sumTreeDebitCredit($this->incomeTree);
        $this->totalIncomeDebit = $incomeTotals['debit'];
        $this->totalIncomeCredit = $incomeTotals['credit'];

        $expenseTotals = $this->sumTreeDebitCredit($this->expenseTree);
        $this->totalExpenseDebit = $expenseTotals['debit'];
        $this->totalExpenseCredit = $expenseTotals['credit'];

        $this->totalOtherDebit = round(collect($this->otherTree)->sum('debit'), 2);
        $this->totalOtherCredit = round(collect($this->otherTree)->sum('credit'), 2);

        $this->totalDebit = round($this->totalIncomeDebit + $this->totalExpenseDebit + $this->totalOtherDebit, 2);
        $this->totalCredit = round($this->totalIncomeCredit + $this->totalExpenseCredit + $this->totalOtherCredit, 2);

        return view('livewire.reports.profit-loss-statement');
    }
}
