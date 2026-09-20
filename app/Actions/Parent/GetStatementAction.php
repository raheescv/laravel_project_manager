<?php

namespace App\Actions\Parent;

use App\Actions\Student\GetStatementAction as LedgerStatementAction;
use App\Models\Sale;
use App\Models\Scopes\AssignedBranchScope;

/**
 * A student's card statement the way a PARENT reads it.
 *
 * The ledger statement (App\Actions\Student\GetStatementAction) is the books:
 * one line per journal entry, worded for the office — "QPay top-up
 * KNJYTXO18M0NCUJDTP7R (confirmation 202609190657488)", and a single canteen
 * purchase split across its gross, tax and discount lines. None of that means
 * anything to a parent.
 *
 * So this action folds the ledger down to what moved on the card:
 *
 *   - one row per journal, netted — a purchase is one line, not three;
 *   - a row that nets to zero is dropped (nothing left the card: a bill paid in
 *     cash at the till, a correction reversed the same day). The Bills tab still
 *     has the purchase;
 *   - plain wording ("Money added", "Canteen purchase") and a detail line a
 *     parent can act on — how it was paid, how many items, the reason the office
 *     typed. Gateway references and accounting remarks never reach the portal;
 *   - purchases carry their sale id, so the row opens the bill.
 *
 * The running balance is the ledger's own, so the closing balance always agrees
 * with the card and with the admin statement.
 *
 * Read-only, so it returns the value directly.
 */
class GetStatementAction
{
    /**
     * @return array{opening: float, closing: float, added: float, spent: float, rows: array<int, array>}
     */
    public function execute(int $accountId, ?string $from = null, ?string $to = null): array
    {
        $ledger = (new LedgerStatementAction())->execute($accountId, $from, $to);

        $groups = [];
        foreach ($ledger['rows'] as $entry) {
            // Entries of one journal are written together, so ordering by date and
            // id already keeps them side by side: grouping never reorders the month.
            $groups[$entry['journal_id'] ?: 'entry-'.$entry['id']][] = $entry;
        }

        $sales = $this->sales($accountId, $groups);

        $rows = [];
        $added = 0.0;
        $spent = 0.0;

        foreach ($groups as $entries) {
            $amount = round(array_sum(array_column($entries, 'credit')) - array_sum(array_column($entries, 'debit')), 2);
            if ($amount == 0.0) {
                continue;
            }

            $first = $entries[0];
            $last = $entries[count($entries) - 1];
            $saleId = $this->modelId($entries, 'Sale');
            $sale = $saleId ? ($sales[$saleId] ?? null) : null;

            $rows[] = [
                'id' => $first['id'],
                'date' => $first['date'],
                'amount' => $amount,
                'balance' => $last['balance'],
                'sale_id' => $sale?->id,
            ] + $this->wording($first, $sale);

            if ($amount > 0) {
                $added += $amount;
            } else {
                $spent -= $amount;
            }
        }

        return [
            'opening' => $ledger['opening'],
            'closing' => $ledger['closing'],
            'added' => round($added, 2),
            'spent' => round($spent, 2),
            'rows' => $rows,
        ];
    }

    /**
     * What the row says. `kind` picks the portal's icon and colour; `title` and
     * `detail` are the only text a parent sees.
     *
     * @param  array<string, mixed>  $entry
     * @return array{kind: string, title: string, detail: string}
     */
    private function wording(array $entry, ?Sale $sale): array
    {
        $online = $entry['model'] === 'QpayTransaction';
        $reason = $this->reason((string) $entry['description']);

        return match ((string) $entry['source']) {
            'student_topup' => [
                'kind' => 'topup',
                'title' => 'Money added',
                'detail' => $online ? 'Paid online' : ($reason ? 'School office · '.$reason : 'Added at the school office'),
            ],
            'student_topup_refund' => $online
                ? ['kind' => 'refund', 'title' => 'Top-up refunded', 'detail' => 'Sent back to the card you paid with']
                : ['kind' => 'adjustment', 'title' => 'Money taken off', 'detail' => $reason ? 'School office · '.$reason : 'Taken off at the school office'],
            'sale' => [
                'kind' => 'purchase',
                'title' => 'Canteen purchase',
                'detail' => $sale ? $this->items((int) $sale->items_count).($sale->branch?->name ? ' · '.$sale->branch->name : '') : 'Paid with the card',
            ],
            'saleReturn', 'sale_return' => [
                'kind' => 'return',
                'title' => 'Items returned',
                'detail' => 'Put back on the card',
            ],
            default => [
                'kind' => 'adjustment',
                'title' => LedgerStatementAction::typeLabel((string) $entry['source']),
                'detail' => $reason ?: 'Recorded by the school office',
            ],
        };
    }

    /**
     * The reason a clerk typed for an office entry, without the bookkeeping prefix
     * ManualEntryAction puts in front of it. Anything else — a gateway reference,
     * an accounting remark — is not for parents, so it is dropped.
     */
    private function reason(string $remarks): string
    {
        foreach (['Card top-up: ', 'Card deduction: '] as $prefix) {
            if (str_starts_with($remarks, $prefix)) {
                return trim(substr($remarks, strlen($prefix)));
            }
        }

        return '';
    }

    private function items(int $count): string
    {
        return $count.' '.($count === 1 ? 'item' : 'items');
    }

    /**
     * The bills behind this month's purchases, in one query.
     *
     * @param  array<int|string, array<int, array<string, mixed>>>  $groups
     * @return array<int, Sale>
     */
    private function sales(int $accountId, array $groups): array
    {
        $ids = [];
        foreach ($groups as $entries) {
            if ($id = $this->modelId($entries, 'Sale')) {
                $ids[] = $id;
            }
        }

        if (! $ids) {
            return [];
        }

        // Branch scope off, like ListBillsAction: it narrows to a STAFF user's
        // branches and would blank the detail whenever a staff session shares the browser.
        return Sale::withoutGlobalScope(AssignedBranchScope::class)
            ->with('branch:id,name')
            ->withCount('items')
            ->where('account_id', $accountId)
            ->whereIn('id', $ids)
            ->get(['id', 'branch_id'])
            ->keyBy('id')
            ->all();
    }

    /** @param  array<int, array<string, mixed>>  $entries */
    private function modelId(array $entries, string $model): ?int
    {
        foreach ($entries as $entry) {
            if ($entry['model'] === $model && $entry['model_id']) {
                return (int) $entry['model_id'];
            }
        }

        return null;
    }
}
