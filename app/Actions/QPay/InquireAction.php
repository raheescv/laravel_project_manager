<?php

namespace App\Actions\QPay;

use App\Models\QpayTransaction;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;

/**
 * Ask QPay what happened to a payment (Action 14) and settle it accordingly.
 *
 * Used for broken transactions (no result reached us) and for tampered returns
 * (the posted status is dropped and QPay is asked instead). A gateway that cannot
 * be reached leaves the payment pending for the next attempt.
 *
 * A payment the office released without an answer (`unresolved`) is still asked
 * about: releasing it only frees the card, it does not decide what happened, and
 * if QPay eventually says the money was taken it is credited then.
 */
class InquireAction
{
    public function execute(QpayTransaction $transaction): array
    {
        // A credit card top-up is asked of the Mastercard Gateway instead.
        if ($transaction->isCreditCard()) {
            return (new \App\Actions\Mpgs\InquireAction())->execute($transaction);
        }

        try {
            if (! $transaction->awaitsResult()) {
                return ['success' => true, 'message' => 'Payment already settled', 'data' => $transaction];
            }

            $values = (new QPayClient(QPaySettings::current()))->inquire($transaction->pun, $transaction->lang ?: 'En');
            $transaction->forceFill(['last_inquired_at' => now()])->save();

            $status = $values['Status'] ?? '';

            if ($status === QPayClient::NOT_FOUND) {
                // QPay never received it: the parent left before paying. Only final once
                // the payment is old enough that it cannot still be in progress.
                if ($transaction->created_at->lte(now()->subMinutes(StartPaymentAction::BROKEN_AFTER_MINUTES))) {
                    $transaction = (new SettleAction())->execute($transaction, [
                        'status' => $status,
                        'message' => $values['StatusMessage'] ?? 'Payment not found at QPay.',
                    ], $values);
                }
            } elseif ($status === QPayClient::SUCCESS && filled($values['OriginalStatus'] ?? null)) {
                $transaction = (new SettleAction())->execute($transaction, [
                    'status' => $values['OriginalStatus'],
                    'message' => $values['OriginalStatusMessage'] ?? null,
                    'amount' => $values['Amount'] ?? null,
                    'confirmation_id' => $values['OriginalConfirmationID'] ?? null,
                    'masked_card' => $values['CardNumber'] ?? null,
                    'response_date' => $values['TransactionResponseDate'] ?? null,
                ], $values);
            } else {
                $transaction->forceFill([
                    'gateway_status' => $status,
                    'gateway_status_message' => $values['StatusMessage'] ?? null,
                ])->save();
            }

            $return['success'] = true;
            $return['message'] = $transaction->awaitsResult() ? 'QPay has no final result yet.' : 'Payment '.strtolower($transaction->statusLabel());
            $return['data'] = $transaction;
        } catch (\Throwable $th) {
            $transaction->forceFill(['last_inquired_at' => now()])->save();
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
