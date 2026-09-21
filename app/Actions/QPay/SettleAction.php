<?php

namespace App\Actions\QPay;

use App\Actions\Student\PostTopupJournalAction;
use App\Models\Branch;
use App\Models\QpayTransaction;
use App\Models\User;
use App\Services\Payment\QPayClient;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Record a gateway's verified outcome for a top-up payment — and, when paid, put
 * the money on the card through the ledger. Shared by both gateways: QPay (debit)
 * and the Mastercard Gateway (credit).
 *
 * Only ever called with an outcome the gateway itself vouched for (a QPay result
 * that passed the secure hash check, a QPay inquiry, an MPGS Retrieve Order).
 * Idempotent: the row is locked, and a payment that already has an outcome is left
 * exactly as it is, so the browser return and the inquiry command can race without
 * crediting a card twice.
 *
 * QPay outcomes carry its status code and minor-unit `amount`; MPGS outcomes say
 * `paid` outright and give `paid_amount` in QAR. `review` parks a payment for a
 * person (money moved in a way the card should not follow blindly).
 *
 * @param  array{status: string, message?: ?string, paid?: bool, amount?: ?string, paid_amount?: float|string|null, review?: ?string, confirmation_id?: ?string, masked_card?: ?string, card_brand?: ?string, funding_method?: ?string, response_date?: ?string}  $outcome
 */
class SettleAction
{
    public function execute(QpayTransaction $transaction, array $outcome, array $payload = []): QpayTransaction
    {
        return DB::transaction(function () use ($transaction, $outcome, $payload) {
            $locked = QpayTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            // `unresolved` counts as still open: the office released it to free the
            // card, not because it knew the outcome, so QPay's answer still decides.
            if (! $locked->awaitsResult()) {
                return $locked;
            }

            $locked->fill([
                'gateway_status' => $outcome['status'],
                'gateway_status_message' => $outcome['message'] ?? null,
                'confirmation_id' => $outcome['confirmation_id'] ?? $locked->confirmation_id,
                'masked_card' => $outcome['masked_card'] ?? $locked->masked_card,
                'card_brand' => $outcome['card_brand'] ?? $locked->card_brand,
                'funding_method' => $outcome['funding_method'] ?? $locked->funding_method,
                'response_date' => $outcome['response_date'] ?? $locked->response_date,
                'payload' => $payload ?: $locked->payload,
            ]);

            if (filled($outcome['review'] ?? null)) {
                $this->review($locked, $outcome['review']);

                return $locked;
            }

            if (! ($outcome['paid'] ?? $outcome['status'] === QPayClient::SUCCESS)) {
                $locked->fill([
                    'status' => QpayTransaction::STATUS_FAILED,
                    'failure_reason' => $outcome['message'] ?? 'Payment was not completed.',
                    'completed_at' => now(),
                ])->save();

                return $locked;
            }

            $paid = array_key_exists('paid_amount', $outcome)
                ? QPayClient::minorUnits($outcome['paid_amount'] ?? 0)
                : ($outcome['amount'] ?? null);
            if ($paid !== QPayClient::minorUnits($locked->amount)) {
                $this->review($locked, $locked->gatewayLabel().' reports '.QPayClient::fromMinorUnits($paid ?? '0').' paid for a top-up of '.$locked->amount.'.');

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
        $settings = $transaction->gatewaySettings();
        $user = User::find($settings->userId);
        if (! $user || ! $settings->paymentAccountId) {
            throw new Exception($transaction->methodLabel().' top-ups are not fully set up (payment account or recording user missing).');
        }

        $response = (new PostTopupJournalAction())->execute($transaction->account_id, (float) $transaction->amount, $settings->paymentAccountId, [
            'date' => now()->toDateString(),
            'branch_id' => $user->default_branch_id ?: Branch::query()->value('id'),
            'reference_no' => $transaction->pun,
            'model' => 'QpayTransaction',
            'model_id' => $transaction->id,
            'remarks' => $transaction->isCreditCard()
                ? 'Credit card top-up '.$transaction->pun.($transaction->confirmation_id ? ' (receipt '.$transaction->confirmation_id.')' : '')
                : 'QPay top-up '.$transaction->pun.($transaction->confirmation_id ? ' (confirmation '.$transaction->confirmation_id.')' : ''),
        ], $user->id);

        if (! $response['success']) {
            throw new Exception($response['message']);
        }

        return $response['data'];
    }

    private function review(QpayTransaction $transaction, string $reason): void
    {
        Log::error('Online top-up paid but not credited', ['gateway' => $transaction->gateway, 'pun' => $transaction->pun, 'reason' => $reason]);

        $transaction->fill([
            'status' => QpayTransaction::STATUS_REVIEW,
            'failure_reason' => $reason,
            'completed_at' => now(),
        ])->save();
    }
}
