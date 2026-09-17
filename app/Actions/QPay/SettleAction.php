<?php

namespace App\Actions\QPay;

use App\Actions\Student\PostTopupJournalAction;
use App\Models\Branch;
use App\Models\QpayTransaction;
use App\Models\User;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Record QPay's verified outcome for a top-up payment — and, when paid, put the
 * money on the card through the ledger.
 *
 * Only ever called with an outcome that has passed the secure hash check (a
 * signed return, or an inquiry response). Idempotent: the row is locked, and a
 * payment that already has an outcome is left exactly as it is, so the browser
 * return and the inquiry command can race without crediting a card twice.
 *
 * @param  array{status: string, message?: ?string, amount?: ?string, confirmation_id?: ?string, masked_card?: ?string, response_date?: ?string}  $outcome
 */
class SettleAction
{
    public function execute(QpayTransaction $transaction, array $outcome, array $payload = []): QpayTransaction
    {
        return DB::transaction(function () use ($transaction, $outcome, $payload) {
            $locked = QpayTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isPending()) {
                return $locked;
            }

            $locked->fill([
                'gateway_status' => $outcome['status'],
                'gateway_status_message' => $outcome['message'] ?? null,
                'confirmation_id' => $outcome['confirmation_id'] ?? $locked->confirmation_id,
                'masked_card' => $outcome['masked_card'] ?? $locked->masked_card,
                'response_date' => $outcome['response_date'] ?? $locked->response_date,
                'payload' => $payload ?: $locked->payload,
            ]);

            if ($outcome['status'] !== QPayClient::SUCCESS) {
                $locked->fill([
                    'status' => QpayTransaction::STATUS_FAILED,
                    'failure_reason' => $outcome['message'] ?? 'Payment was not completed.',
                    'completed_at' => now(),
                ])->save();

                return $locked;
            }

            if (($outcome['amount'] ?? null) !== QPayClient::minorUnits($locked->amount)) {
                $this->review($locked, 'QPay reports '.QPayClient::fromMinorUnits($outcome['amount'] ?? '0').' paid for a top-up of '.$locked->amount.'.');

                return $locked;
            }

            try {
                // A savepoint, so a booking failure leaves the paid row to be reviewed rather than rolled away.
                $journal = DB::transaction(fn () => $this->credit($locked));
            } catch (\Throwable $th) {
                $this->review($locked, $th->getMessage());

                return $locked;
            }

            $locked->fill([
                'status' => QpayTransaction::STATUS_SUCCESS,
                'journal_id' => $journal->id,
                'completed_at' => now(),
                'failure_reason' => null,
            ])->save();

            return $locked;
        });
    }

    private function credit(QpayTransaction $transaction)
    {
        $settings = QPaySettings::current();
        $user = User::find($settings->userId);
        if (! $user || ! $settings->paymentAccountId) {
            throw new Exception('QPay top-ups are not fully set up (payment account or recording user missing).');
        }

        $response = (new PostTopupJournalAction())->execute($transaction->account_id, (float) $transaction->amount, $settings->paymentAccountId, [
            'date' => now()->toDateString(),
            'branch_id' => $user->default_branch_id ?: Branch::query()->value('id'),
            'reference_no' => $transaction->pun,
            'model' => 'QpayTransaction',
            'model_id' => $transaction->id,
            'remarks' => 'QPay top-up '.$transaction->pun.($transaction->confirmation_id ? ' (confirmation '.$transaction->confirmation_id.')' : ''),
        ], $user->id);

        if (! $response['success']) {
            throw new Exception($response['message']);
        }

        return $response['data'];
    }

    private function review(QpayTransaction $transaction, string $reason): void
    {
        Log::error('QPay top-up paid but not credited', ['pun' => $transaction->pun, 'reason' => $reason]);

        $transaction->fill([
            'status' => QpayTransaction::STATUS_REVIEW,
            'failure_reason' => $reason,
            'completed_at' => now(),
        ])->save();
    }
}
