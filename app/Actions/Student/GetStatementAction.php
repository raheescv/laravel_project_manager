<?php

namespace App\Actions\Student;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Carbon;

/**
 * A student's card statement for a period: the balance brought forward, every
 * ledger line on the student's account (top-ups, purchases, returns, refunds)
 * with a running balance, and the closing balance.
 *
 * Shared by the admin student page and the parent portal, so both always show
 * the same numbers the books hold. All branches, like GetBalanceAction.
 *
 * Read-only, so it returns the value directly.
 */
class GetStatementAction
{
    /**
     * @return array{opening: float, closing: float, credit: float, debit: float, rows: array<int, array>}
     */
    public function execute(int $accountId, ?string $from = null, ?string $to = null): array
    {
        $account = Account::find($accountId);
        $opening = $account ? (float) $account->opening_credit - (float) $account->opening_debit : 0.0;

        $base = JournalEntry::query()->where('account_id', $accountId);

        if ($from) {
            $before = (clone $base)->where('date', '<', Carbon::parse($from)->toDateString())
                ->selectRaw('COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) as balance')
                ->value('balance');
            $opening += (float) $before;
        }

        $entries = (clone $base)
            ->when($from, fn ($q) => $q->where('date', '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn ($q) => $q->where('date', '<=', Carbon::parse($to)->toDateString()))
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'journal_id', 'date', 'source', 'description', 'remarks', 'model', 'model_id', 'debit', 'credit', 'reference_number']);

        $running = round($opening, 2);
        $rows = [];
        foreach ($entries as $entry) {
            $running = round($running + (float) $entry->credit - (float) $entry->debit, 2);
            $rows[] = [
                'id' => $entry->id,
                'journal_id' => $entry->journal_id,
                'date' => $entry->date,
                'type' => self::typeLabel((string) $entry->source),
                'source' => $entry->source,
                'description' => $entry->remarks ?: $entry->description,
                'model' => $entry->model,
                'model_id' => $entry->model_id,
                'reference' => $entry->reference_number,
                'credit' => (float) $entry->credit,
                'debit' => (float) $entry->debit,
                'balance' => $running,
            ];
        }

        return [
            'opening' => round($opening, 2),
            'closing' => $running,
            'credit' => round($entries->sum('credit'), 2),
            'debit' => round($entries->sum('debit'), 2),
            'rows' => $rows,
        ];
    }

    public static function typeLabel(string $source): string
    {
        return match ($source) {
            'student_topup' => 'Top-up',
            'student_topup_refund' => 'Top-up refund',
            'sale' => 'Purchase',
            'saleReturn', 'sale_return' => 'Return',
            default => ucwords(str_replace('_', ' ', $source)),
        };
    }
}
