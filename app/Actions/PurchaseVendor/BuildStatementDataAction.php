<?php

namespace App\Actions\PurchaseVendor;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;

class BuildStatementDataAction
{
    public function execute(Account $vendor, ?string $fromDate = null, ?string $toDate = null, ?int $limit = null): array
    {
        $isOpeningExcluded = in_array(strtolower($vendor->account_type ?? ''), ['income', 'expense'], true);

        $openingSums = $this->baseQuery($vendor)
            ->when($fromDate, fn ($query, $value) => $query->whereDate('date', '<', $value))
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $openingDebit = $isOpeningExcluded ? 0 : (float) ($openingSums->debit ?? 0) + (float) ($vendor->opening_debit ?? 0);
        $openingCredit = $isOpeningExcluded ? 0 : (float) ($openingSums->credit ?? 0) + (float) ($vendor->opening_credit ?? 0);
        $openingBalance = $openingDebit - $openingCredit;

        $statementQuery = $this->baseQuery($vendor)
            ->with(['counterAccount:id,name'])
            ->when($fromDate, fn ($query, $value) => $query->whereDate('date', '>=', $value))
            ->when($toDate, fn ($query, $value) => $query->whereDate('date', '<=', $value))
            ->orderBy('date')
            ->orderBy('id');

        $totals = (clone $statementQuery)
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit, COUNT(*) as entries')
            ->first();

        $rowsQuery = clone $statementQuery;
        if ($limit) {
            $rowsQuery->limit($limit);
        }

        $rows = $rowsQuery->get()->values();

        $runningBalance = $openingBalance;
        $statementRows = $rows->map(function (JournalEntry $entry) use (&$runningBalance) {
            $runningBalance += (float) $entry->debit - (float) $entry->credit;

            return [
                'id' => $entry->id,
                'journal_id' => $entry->journal_id,
                'date' => $entry->date,
                'particulars' => $entry->counterAccount?->name ?: ($entry->person_name ?: ($entry->description ?: '-')),
                'description' => $entry->description,
                'reference_number' => $entry->reference_number,
                'remarks' => $entry->remarks ?: $entry->journal_remarks,
                'model' => $entry->model,
                'model_invoice_no' => $entry->purchase?->invoice_no,
                'model_id' => $entry->model_id,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'balance' => $runningBalance,
                'balance_label' => $this->formatBalanceLabel($runningBalance),
                'can_view_payment_voucher' => $entry->model === 'PurchasePayment'
                    && (float) $entry->debit > 0
                    && filled($entry->journal_id),
            ];
        });

        $totalDebit = $openingDebit + (float) ($totals->debit ?? 0);
        $totalCredit = $openingCredit + (float) ($totals->credit ?? 0);
        $closingBalance = $totalDebit - $totalCredit;

        return [
            'summary' => [
                'opening_debit' => $openingDebit,
                'opening_credit' => $openingCredit,
                'opening_balance' => $openingBalance,
                'opening_balance_label' => $this->formatBalanceLabel($openingBalance),
                'period_debit' => (float) ($totals->debit ?? 0),
                'period_credit' => (float) ($totals->credit ?? 0),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $closingBalance,
                'closing_balance_label' => $this->formatBalanceLabel($closingBalance),
                'entry_count' => (int) ($totals->entries ?? 0),
                'displayed_count' => $statementRows->count(),
            ],
            'rows' => $statementRows,
        ];
    }

    private function baseQuery(Account $vendor): Builder
    {
        return JournalEntry::query()
            ->where('account_id', $vendor->id)
            ->when(session('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId));
    }

    private function formatBalanceLabel(float $balance): string
    {
        if (round($balance, 2) === 0.0) {
            return currency(0);
        }

        return currency(abs($balance)).($balance < 0 ? ' Cr' : ' Dr');
    }
}
