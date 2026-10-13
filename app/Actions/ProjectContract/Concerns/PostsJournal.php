<?php

namespace App\Actions\ProjectContract\Concerns;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Models\Journal;
use App\Models\ProjectContract;
use Exception;

/**
 * Writes one balanced journal for a project-contract event through the shared
 * Journal\CreateAction, using the codebase's paired-entry shape.
 */
trait PostsJournal
{
    /**
     * Debit one account and credit another for the same amount, as two
     * mirrored rows that each carry the other side as `counter_account_id`.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function makeEntryPair(int $debitAccountId, int $creditAccountId, float $amount, string $remarks, string $model, int $modelId, int $userId): array
    {
        $base = ['created_by' => $userId, 'remarks' => $remarks, 'model' => $model, 'model_id' => $modelId];

        return [
            array_merge($base, ['account_id' => $debitAccountId, 'counter_account_id' => $creditAccountId, 'debit' => $amount, 'credit' => 0]),
            array_merge($base, ['account_id' => $creditAccountId, 'counter_account_id' => $debitAccountId, 'debit' => 0, 'credit' => $amount]),
        ];
    }

    /**
     * Post Dr $debitAccountId / Cr $creditAccountId and return the journal.
     *
     * @param  array{date: string, description: string, model: string, model_id: int, reference_number?: string|null, remarks?: string|null}  $header
     * @param  array<string, mixed>  $entryExtra  Cheque details and the like, copied onto both rows.
     */
    protected function postPair(ProjectContract $contract, array $header, int $debitAccountId, int $creditAccountId, float $amount, string $remarks, int $userId, array $entryExtra = []): Journal
    {
        if ($amount <= 0) {
            throw new Exception('A journal needs an amount above zero.');
        }

        $entries = array_map(
            fn (array $entry): array => $entry + $entryExtra,
            $this->makeEntryPair($debitAccountId, $creditAccountId, round($amount, 2), $remarks, $header['model'], $header['model_id'], $userId)
        );

        $response = (new JournalCreateAction())->execute([
            'tenant_id' => $contract->tenant_id,
            'branch_id' => $contract->branch_id,
            'date' => $header['date'],
            'description' => $header['description'],
            'remarks' => $header['remarks'] ?? null,
            'reference_number' => $header['reference_number'] ?? $contract->reference_no,
            'person_name' => $contract->vendor?->name,
            'source' => 'project_contract',
            'model' => $header['model'],
            'model_id' => $header['model_id'],
            'created_by' => $userId,
            'entries' => $entries,
        ]);

        if (! $response['success']) {
            throw new Exception($response['message']);
        }

        return $response['data'];
    }
}
