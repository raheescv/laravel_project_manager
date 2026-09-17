<?php

namespace App\Actions\Student;

use App\Models\Account;
use App\Models\Branch;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * A top-up or deduction recorded at the school office, without QPay — money
 * handed over the counter, a correction, a leaving student's balance paid back.
 *
 * Goes through the same journal as an online top-up (PostTopupJournalAction), so
 * the card balance stays the student account's ledger and nothing has a second
 * source of truth:
 *
 *   add    → Dr the payment method (cash/bank) / Cr the student
 *   deduct → Dr the student / Cr the payment method
 *
 * A reason is required — this is the one way a balance moves without a payment
 * or a purchase behind it, so the statement has to say who did it and why.
 * A deduction may not take the card below zero: overdraft is for the canteen
 * till, not for office corrections.
 *
 * Self-transacting: the account row is locked while the balance is checked, so
 * two clerks cannot both deduct the same money.
 */
class ManualEntryAction
{
    public const ADD = 'add';

    public const DEDUCT = 'deduct';

    public function execute(int $accountId, float $amount, int $paymentAccountId, string $direction, string $reason, ?string $date, int $userId): array
    {
        try {
            $amount = round($amount, 2);
            $reason = trim($reason);
            $direction = $direction === self::DEDUCT ? self::DEDUCT : self::ADD;

            if ($amount <= 0) {
                throw new Exception('Enter an amount greater than zero.', 1);
            }
            if ($reason === '') {
                throw new Exception('Enter a reason for this entry.', 1);
            }
            if (! in_array($paymentAccountId, array_map('intval', tenant_cache('payment_methods', []) ?: []), true)) {
                throw new Exception('Choose one of the configured payment methods.', 1);
            }

            $journal = DB::transaction(function () use ($accountId, $amount, $paymentAccountId, $direction, $reason, $date, $userId) {
                $student = Account::student()->whereKey($accountId)->lockForUpdate()->first();
                if (! $student) {
                    throw new Exception("Student not found with the specified ID: $accountId.", 1);
                }

                if ($direction === self::DEDUCT) {
                    $balance = (new GetBalanceAction())->execute($student->id);
                    if ($amount > $balance) {
                        throw new Exception('The card holds '.currency($balance).', so '.currency($amount).' cannot be taken off it.', 1);
                    }
                }

                $response = (new PostTopupJournalAction())->execute($student->id, $amount, $paymentAccountId, [
                    'date' => $date ?: now()->toDateString(),
                    'branch_id' => session('branch_id') ?: Branch::query()->value('id'),
                    'model' => 'StudentManualEntry',
                    'model_id' => $student->id,
                    'remarks' => ($direction === self::ADD ? 'Card top-up: ' : 'Card deduction: ').$reason,
                ], $userId, refund: $direction === self::DEDUCT);

                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }

                return $response['data'];
            });

            $return['success'] = true;
            $return['message'] = $direction === self::ADD ? 'Top-up recorded' : 'Amount deducted from the card';
            $return['data'] = $journal;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
