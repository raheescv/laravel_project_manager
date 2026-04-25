<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\JournalEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the full Profit & Loss report payload for a given period and
 * (optional) branch.
 *
 * The app uses a perpetual-inventory bookkeeping model: purchases hit
 * the Inventory asset directly and Cost of Goods Sold is booked at
 * sale time. The legacy `purchase` / `purchase_returns` ledger heads
 * are never posted to, so Net Purchase is derived from Inventory
 * movements tagged with purchase-related `model` values, and COGS is
 * surfaced implicitly through `Opening Stock + Net Purchase − Closing
 * Stock` rather than shown as a separate Direct Expense row.
 */
class ProfitLossReportService
{
    /**
     * Slugs of the accounting heads that flow into the Trading Account
     * and therefore must never be shown again under Direct/Indirect
     * Income or Expense (to prevent double-counting).
     */
    private const ACCOUNT_SLUGS = [
        'inventory',
        'sale',
        'sales_returns',
        'purchase',
        'purchase_returns',
        'cost_of_goods_sold',
    ];

    /**
     * Journal entry `model` values whose Inventory movements represent
     * purchase activity. Debits = goods in (Purchase/Grn), credits =
     * goods back to vendor (PurchaseReturn).
     */
    private const PURCHASE_INVENTORY_MODELS = [
        'Purchase',
        'PurchaseReturn',
        'Grn',
    ];

    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?int $branchId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $tradingAccountIds = $this->tradingAccountIds();
        $periodBalances = $this->fetchPeriodBalances();
        $inventoryId = $tradingAccountIds['inventory'] ?? null;
        [$openingStock, $closingStock] = $this->stockValues($inventoryId);
        $netPurchase = $this->netPurchase($inventoryId);
        $netSale = $this->netSale($tradingAccountIds, $periodBalances);

        $excludeIds = array_values(array_filter($tradingAccountIds));
        $incomeStructure = $this->categoryStructure('income', $periodBalances, $excludeIds);
        $expenseStructure = $this->categoryStructure('expense', $periodBalances, $excludeIds);

        $totals = $this->extractTotals($incomeStructure, $expenseStructure);
        $profitLoss = $this->profitLossMath($openingStock, $closingStock, $netPurchase, $netSale, $totals);

        return [
            'openingStock' => $openingStock,
            'closingStock' => $closingStock,
            'netPurchase' => $netPurchase,
            'netSale' => $netSale,
            'directIncomeStructure' => $incomeStructure,
            'directExpenseStructure' => $expenseStructure,
            ...$totals,
            ...$profitLoss,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function tradingAccountIds(): array
    {
        $map = Cache::get('accounts_slug_id_map', []);

        $ids = [];
        foreach (self::ACCOUNT_SLUGS as $slug) {
            $ids[$slug] = isset($map[$slug]) ? (int) $map[$slug] : null;
        }

        return $ids;
    }

    /**
     * Fetch all journal entry balances for the period in a single query.
     */
    private function fetchPeriodBalances(): Collection
    {
        return JournalEntry::query()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Net balance for a single account from pre-fetched period data.
     * Expense/Asset → debit − credit; otherwise credit − debit. Negative
     * values are clamped to zero to keep the presentation clean.
     */
    private function accountBalance(?int $accountId, string $accountType, Collection $periodBalances): float
    {
        if (! $accountId) {
            return 0.0;
        }

        $balance = $periodBalances->get($accountId);
        if (! $balance) {
            return 0.0;
        }

        $net = in_array($accountType, ['expense', 'asset'], true)
            ? (float) $balance->total_debit - (float) $balance->total_credit
            : (float) $balance->total_credit - (float) $balance->total_debit;

        return max(0, $net);
    }

    /**
     * Opening = Inventory balance strictly before start date.
     * Closing = Inventory balance up to and including end date.
     *
     * @return array{0: float, 1: float}
     */
    private function stockValues(?int $inventoryId): array
    {
        if (! $inventoryId) {
            return [0.0, 0.0];
        }

        $base = JournalEntry::query()
            ->where('account_id', $inventoryId)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit');

        $opening = (clone $base)->where('date', '<', $this->startDate)->first();
        $closing = (clone $base)->where('date', '<=', $this->endDate)->first();

        return [
            max(0, (float) ($opening->total_debit ?? 0) - (float) ($opening->total_credit ?? 0)),
            max(0, (float) ($closing->total_debit ?? 0) - (float) ($closing->total_credit ?? 0)),
        ];
    }

    /**
     * Net Purchase = Inventory debits − credits for purchase-related
     * journal entries during the period.
     */
    private function netPurchase(?int $inventoryId): float
    {
        if (! $inventoryId) {
            return 0.0;
        }

        $result = JournalEntry::query()
            ->where('account_id', $inventoryId)
            ->whereIn('model', self::PURCHASE_INVENTORY_MODELS)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as net')
            ->first();

        return max(0, (float) ($result->net ?? 0));
    }

    /**
     * Net Sale = Sale − Sales Returns, read from their respective ledgers
     * (both of which do receive journal entries).
     *
     * @param  array<string, int|null>  $tradingAccountIds
     */
    private function netSale(array $tradingAccountIds, Collection $periodBalances): float
    {
        $sale = $this->accountBalance($tradingAccountIds['sale'] ?? null, 'income', $periodBalances);
        $returns = $this->accountBalance($tradingAccountIds['sales_returns'] ?? null, 'expense', $periodBalances);

        return max(0, $sale - $returns);
    }

    /**
     * Build the Direct/Indirect income or expense hierarchy.
     *
     * @param  list<int>  $excludeAccountIds
     * @return list<array{id:int,name:string,total:float,groups:list<array<string,mixed>>,directAccounts:list<array{id:int,name:string,amount:float}>}>
     */
    private function categoryStructure(string $accountType, Collection $periodBalances, array $excludeAccountIds): array
    {
        $masterNames = $accountType === 'income'
            ? ['Direct Income', 'Indirect Income']
            : ['Direct Expense', 'Indirect Expense'];

        $masters = AccountCategory::whereIn('name', $masterNames)
            ->whereNull('parent_id')
            ->with([
                'accounts' => fn ($q) => $q->where('account_type', $accountType),
                'children.accounts' => fn ($q) => $q->where('account_type', $accountType),
            ])
            ->get()
            ->keyBy('name');

        $structure = [];
        foreach ($masterNames as $name) {
            $master = $masters->get($name);
            if (! $master) {
                continue;
            }

            $directAccounts = $this->accountsToRows($master->accounts, $accountType, $periodBalances, $excludeAccountIds);

            $groups = [];
            foreach ($master->children as $group) {
                $rows = $this->accountsToRows($group->accounts, $accountType, $periodBalances, $excludeAccountIds);
                $groups[] = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'total' => array_sum(array_column($rows, 'amount')),
                    'accounts' => $rows,
                ];
            }

            $structure[] = [
                'id' => $master->id,
                'name' => $master->name,
                'total' => array_sum(array_column($directAccounts, 'amount'))
                    + array_sum(array_column($groups, 'total')),
                'groups' => $groups,
                'directAccounts' => $directAccounts,
            ];
        }

        $this->appendUncategorized($structure, $accountType, $periodBalances, $excludeAccountIds);

        return $structure;
    }

    /**
     * Map a collection of accounts into flat rows for the view.
     *
     * @param  iterable<int, Account>  $accounts
     * @param  list<int>  $excludeAccountIds
     * @return list<array{id:int,name:string,amount:float}>
     */
    private function accountsToRows(iterable $accounts, string $accountType, Collection $periodBalances, array $excludeAccountIds): array
    {
        $rows = [];
        foreach ($accounts as $account) {
            if (in_array($account->id, $excludeAccountIds, true)) {
                continue;
            }

            $rows[] = [
                'id' => $account->id,
                'name' => $account->name,
                'amount' => $this->accountBalance($account->id, $accountType, $periodBalances),
            ];
        }

        return $rows;
    }

    /**
     * Fold accounts that have no category into the Indirect bucket.
     *
     * @param  list<array<string, mixed>>  $structure
     * @param  list<int>  $excludeAccountIds
     */
    private function appendUncategorized(array &$structure, string $accountType, Collection $periodBalances, array $excludeAccountIds): void
    {
        $uncategorized = Account::query()
            ->where('account_type', $accountType)
            ->whereNull('account_category_id')
            ->when($excludeAccountIds, fn ($q) => $q->whereNotIn('id', $excludeAccountIds))
            ->get();

        if ($uncategorized->isEmpty()) {
            return;
        }

        $rows = $this->accountsToRows($uncategorized, $accountType, $periodBalances, $excludeAccountIds);
        if (empty($rows)) {
            return;
        }

        $rowsTotal = array_sum(array_column($rows, 'amount'));
        $targetName = $accountType === 'income' ? 'Indirect Income' : 'Indirect Expense';

        foreach ($structure as $index => $category) {
            if ($category['name'] === $targetName) {
                $structure[$index]['directAccounts'] = array_merge($structure[$index]['directAccounts'], $rows);
                $structure[$index]['total'] += $rowsTotal;

                return;
            }
        }

        $indirectCategory = AccountCategory::query()
            ->where('name', $targetName)
            ->whereNull('parent_id')
            ->first();

        $structure[] = [
            'id' => $indirectCategory?->id ?? 0,
            'name' => $targetName,
            'total' => $rowsTotal,
            'groups' => [],
            'directAccounts' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $incomeStructure
     * @param  list<array<string, mixed>>  $expenseStructure
     * @return array{directIncome:float,indirectIncome:float,directExpense:float,indirectExpense:float}
     */
    private function extractTotals(array $incomeStructure, array $expenseStructure): array
    {
        $pick = fn (array $structure, string $name): float => (float) (collect($structure)->firstWhere('name', $name)['total'] ?? 0);

        return [
            'directIncome' => $pick($incomeStructure, 'Direct Income'),
            'indirectIncome' => $pick($incomeStructure, 'Indirect Income'),
            'directExpense' => $pick($expenseStructure, 'Direct Expense'),
            'indirectExpense' => $pick($expenseStructure, 'Indirect Expense'),
        ];
    }

    /**
     * Gross and Net Profit/Loss via the classic T-Account method.
     *
     * Trading Account:
     *   Left:  Opening Stock + Net Purchase + Direct Expense + [Gross Profit]
     *   Right: Net Sale + Closing Stock + Direct Income + [Gross Loss]
     *
     * P&L Account:
     *   Left:  Gross Loss + Indirect Expense + [Net Profit]
     *   Right: Gross Profit + Indirect Income + [Net Loss]
     *
     * @param  array{directIncome:float,indirectIncome:float,directExpense:float,indirectExpense:float}  $totals
     * @return array{grossLoss:float,grossProfit:float,netProfitAmount:float,netLossAmount:float,leftTotal1:float,rightTotal1:float,leftTotal2:float,rightTotal2:float}
     */
    private function profitLossMath(float $openingStock, float $closingStock, float $netPurchase, float $netSale, array $totals): array
    {
        $tradingLeft = $openingStock + $netPurchase + $totals['directExpense'];
        $tradingRight = $netSale + $closingStock + $totals['directIncome'];
        $grossProfit = max(0, $tradingRight - $tradingLeft);
        $grossLoss = max(0, $tradingLeft - $tradingRight);

        $plLeft = $grossLoss + $totals['indirectExpense'];
        $plRight = $grossProfit + $totals['indirectIncome'];
        $netProfitAmount = max(0, $plRight - $plLeft);
        $netLossAmount = max(0, $plLeft - $plRight);

        return [
            'grossLoss' => $grossLoss,
            'grossProfit' => $grossProfit,
            'netProfitAmount' => $netProfitAmount,
            'netLossAmount' => $netLossAmount,
            'leftTotal1' => $tradingLeft + $grossProfit,
            'rightTotal1' => $tradingRight + $grossLoss,
            'leftTotal2' => $plLeft + $netProfitAmount,
            'rightTotal2' => $plRight + $netLossAmount,
        ];
    }
}
