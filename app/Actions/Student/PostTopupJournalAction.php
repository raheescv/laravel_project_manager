<?php

namespace App\Actions\Student;

use App\Actions\Journal\CreateAction;
use App\Models\Account;
use Exception;

/**
 * Put money on a student's card, or take it back off for a refund, through the
 * ledger — the only way a card balance ever changes outside a sale or return.
 *
 *   top-up:  Dr money account (e.g. the QPay bank account) / Cr student account
 *   refund:  Dr student account / Cr money account
 *
 * @param  array{date?: string, branch_id: int, reference_no?: ?string, model: string, model_id: int, remarks?: string}  $meta
 */
class PostTopupJournalAction
{
    public function execute(int $studentAccountId, float $amount, int $moneyAccountId, array $meta, int $userId, bool $refund = false): array
    {
        try {
            $amount = round($amount, 2);
            if ($amount <= 0) {
                throw new Exception('The amount must be greater than zero.', 1);
            }

            $student = Account::student()->find($studentAccountId);
            if (! $student) {
                throw new Exception("Student not found with the specified ID: $studentAccountId.", 1);
            }
            if ($studentAccountId === $moneyAccountId) {
                throw new Exception('The payment account cannot be the student\'s own account.', 1);
            }

            $remarks = $meta['remarks'] ?? ($refund
                ? 'Card top-up refunded to '.$student->name
                : 'Card top-up for '.$student->name);

            $base = ['created_by' => $userId, 'remarks' => $remarks, 'model' => $meta['model'], 'model_id' => $meta['model_id']];
            $debit = $refund ? 0 : $amount;
            $credit = $refund ? $amount : 0;

            $response = (new CreateAction())->execute([
                'tenant_id' => $student->tenant_id,
                'date' => $meta['date'] ?? now()->toDateString(),
                'branch_id' => $meta['branch_id'],
                'description' => ($refund ? 'Card Refund:' : 'Card Top-up:').$student->name,
                'reference_number' => $meta['reference_no'] ?? null,
                'person_name' => $student->name,
                'source' => $refund ? 'student_topup_refund' : 'student_topup',
                'model' => $meta['model'],
                'model_id' => $meta['model_id'],
                'created_by' => $userId,
                'entries' => [
                    array_merge($base, ['account_id' => $moneyAccountId, 'counter_account_id' => $student->id, 'debit' => $debit, 'credit' => $credit]),
                    array_merge($base, ['account_id' => $student->id, 'counter_account_id' => $moneyAccountId, 'debit' => $credit, 'credit' => $debit]),
                ],
            ]);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = $refund ? 'Card refund posted' : 'Card top-up posted';
            $return['data'] = $response['data'];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
