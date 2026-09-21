<?php

namespace App\Actions\Mpgs;

use App\Actions\QPay\SettleAction;
use App\Models\QpayTransaction;
use App\Services\Payment\MpgsClient;
use App\Support\Payment\MpgsSettings;

/**
 * Ask the Mastercard Gateway what happened to a credit card top-up (RETRIEVE_ORDER)
 * and settle it accordingly. The only way an MPGS payment is ever settled — the
 * parent's return, the scheduled chase and the office's "check" button all come here.
 *
 * Captured → credited. Money moved in any other way (authorised only, refunded or
 * disputed in Merchant Administration) → parked for review. Declined, cancelled or
 * never paid → failed, but only once the parent has left the page for good:
 * Hosted Checkout lets them retry a declined card on the same order, so a decline
 * is not final while the page may still be open ($final — they were sent back
 * after their last retry — or the payment is [ABANDONED_AFTER_MINUTES] old).
 */
class InquireAction
{
    /** The payment page times out at 30 minutes; after this nothing more can be paid on the order. */
    public const ABANDONED_AFTER_MINUTES = 60;

    public const NOT_FOUND = 'NOT_FOUND';

    public function execute(QpayTransaction $transaction, bool $final = false): array
    {
        try {
            if (! $transaction->awaitsResult()) {
                return ['success' => true, 'message' => 'Payment already settled', 'data' => $transaction];
            }

            $order = (new MpgsClient(MpgsSettings::current()))->retrieveOrder($transaction->pun);
            $transaction->forceFill(['last_inquired_at' => now()])->save();

            $over = $final || $transaction->created_at->lte(now()->subMinutes(self::ABANDONED_AFTER_MINUTES));
            $cancelled = $transaction->status === QpayTransaction::STATUS_CANCELLED;

            if ($order === null) {
                // No card was ever submitted on the page.
                if ($over) {
                    $transaction = (new SettleAction())->execute($transaction, [
                        'status' => self::NOT_FOUND,
                        'message' => $cancelled ? 'You cancelled the payment.' : 'No card payment was made.',
                        'paid' => false,
                    ]);
                } else {
                    $transaction->forceFill(['gateway_status' => self::NOT_FOUND, 'gateway_status_message' => 'The gateway has no payment for this order yet.'])->save();
                }
            } else {
                $outcome = self::outcome($order);
                $status = (string) ($order['status'] ?? '');

                if ($outcome['paid'] || filled($outcome['review']) || ($over && in_array($status, MpgsClient::NOT_PAID, true))) {
                    $transaction = (new SettleAction())->execute($transaction, $outcome, self::payload($order));
                } else {
                    $transaction->forceFill(['gateway_status' => $outcome['status'], 'gateway_status_message' => $outcome['message']])->save();
                }
            }

            $return['success'] = true;
            $return['message'] = $transaction->awaitsResult() ? 'The card gateway has no final result yet.' : 'Payment '.strtolower($transaction->statusLabel());
            $return['data'] = $transaction;
        } catch (\Throwable $th) {
            $transaction->forceFill(['last_inquired_at' => now()])->save();
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * A Retrieve Order response as SettleAction's outcome.
     *
     * @return array{status: string, message: string, paid: bool, paid_amount: ?string, review: ?string, confirmation_id: ?string, masked_card: ?string, card_brand: ?string, funding_method: ?string}
     */
    public static function outcome(array $order): array
    {
        $status = (string) ($order['status'] ?? '');
        $payment = MpgsClient::lastPayment($order);
        $code = $payment['response']['gatewayCode'] ?? null;
        $card = $order['sourceOfFunds']['provided']['card'] ?? [];
        $paid = ($order['result'] ?? null) === 'SUCCESS' && in_array($status, MpgsClient::PAID, true);

        $review = match (true) {
            ($order['currency'] ?? MpgsClient::CURRENCY) !== MpgsClient::CURRENCY => 'The card gateway reports the order in '.$order['currency'].', not '.MpgsClient::CURRENCY.'.',
            in_array($status, MpgsClient::NEEDS_REVIEW, true) => 'The card gateway reports this order as '.$status.'. Check it in Merchant Administration before the card is credited.',
            default => null,
        };

        return [
            // A decline is best told by the bank's code; anything else by where the order stands.
            'status' => mb_substr(! $paid && $code && $code !== 'APPROVED' ? $code : ($status ?: (string) ($order['result'] ?? '')), 0, 30),
            'message' => $paid ? 'Payment captured.' : ($review ? $status : MpgsClient::describe($code ?: $status)),
            'paid' => $paid,
            'paid_amount' => $paid ? (string) ($order['totalCapturedAmount'] ?? $order['amount'] ?? '0') : null,
            'review' => $review,
            'confirmation_id' => ($payment['transaction']['receipt'] ?? null) ?: ($payment['transaction']['authorizationCode'] ?? null),
            'masked_card' => isset($card['number']) ? mb_substr((string) $card['number'], 0, 19) : null,
            'card_brand' => isset($card['brand']) ? mb_substr((string) $card['brand'], 0, 20) : null,
            'funding_method' => isset($card['fundingMethod']) ? mb_substr((string) $card['fundingMethod'], 0, 10) : null,
        ];
    }

    /** What is worth keeping from the order: enough to trace it, nothing of the card beyond the mask. */
    private static function payload(array $order): array
    {
        $payment = MpgsClient::lastPayment($order);

        return array_filter([
            'order_id' => $order['id'] ?? null,
            'result' => $order['result'] ?? null,
            'status' => $order['status'] ?? null,
            'amount' => $order['amount'] ?? null,
            'currency' => $order['currency'] ?? null,
            'total_captured' => $order['totalCapturedAmount'] ?? null,
            'total_refunded' => $order['totalRefundedAmount'] ?? null,
            'authentication_status' => $order['authenticationStatus'] ?? null,
            'transaction_id' => $payment['transaction']['id'] ?? null,
            'transaction_type' => $payment['transaction']['type'] ?? null,
            'gateway_code' => $payment['response']['gatewayCode'] ?? null,
            'acquirer_code' => $payment['response']['acquirerCode'] ?? null,
            'acquirer_message' => $payment['response']['acquirerMessage'] ?? null,
            'authorization_code' => $payment['transaction']['authorizationCode'] ?? null,
            'receipt' => $payment['transaction']['receipt'] ?? null,
            'card' => $order['sourceOfFunds']['provided']['card']['number'] ?? null,
            'brand' => $order['sourceOfFunds']['provided']['card']['brand'] ?? null,
            'funding_method' => $order['sourceOfFunds']['provided']['card']['fundingMethod'] ?? null,
        ], fn ($value) => $value !== null);
    }
}
