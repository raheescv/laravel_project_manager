<?php

namespace App\Services\Payment;

use App\Models\ApiLog;
use App\Models\User;
use App\Support\Payment\MpgsSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mastercard Gateway (MPGS) REST API, Hosted Checkout model — the credit card
 * side of student card top-ups.
 *
 * Three calls: INITIATE_CHECKOUT opens a session the portal hands to the
 * gateway's checkout.min.js (the parent pays on the gateway's page, never ours);
 * RETRIEVE_ORDER is the authoritative answer on what happened; REFUND gives it back.
 * The browser's return (`resultIndicator`) is never trusted on its own — every
 * outcome is read back with RETRIEVE_ORDER.
 *
 * Auth is HTTP Basic "merchant.<id>" : API password. Every call is logged to
 * api_logs; the password travels only in the Authorization header, never logged.
 */
class MpgsClient
{
    public const CURRENCY = 'QAR';

    public const LOG_CHECKOUT = 'MPGS Checkout';

    public const LOG_RETRIEVE = 'MPGS Retrieve Order';

    public const LOG_REFUND = 'MPGS Refund';

    /** Order statuses meaning the money was taken and is still with the merchant. */
    public const PAID = ['CAPTURED'];

    /** Money moved in a way a person must look at before the card is touched. */
    public const NEEDS_REVIEW = ['AUTHORIZED', 'PARTIALLY_CAPTURED', 'PARTIALLY_REFUNDED', 'REFUNDED', 'EXCESSIVELY_REFUNDED', 'REFUND_REQUESTED', 'DISPUTED', 'CHARGEBACK_PROCESSED'];

    /**
     * No money taken — yet. Hosted Checkout lets the payer retry on the same order,
     * so these only become final when the payer has left the page for good.
     */
    public const NOT_PAID = ['FAILED', 'CANCELLED', 'AUTHENTICATION_UNSUCCESSFUL'];

    /** How long the parent may stay on the payment page (the API's maximum). */
    public const PAGE_TIMEOUT_SECONDS = 1800;

    public function __construct(private readonly MpgsSettings $settings) {}

    /** 100 → "100.00": MPGS amounts are decimal strings. */
    public static function amount(float|string $amount): string
    {
        return number_format(round((float) $amount, 2), 2, '.', '');
    }

    /**
     * INITIATE_CHECKOUT for a PURCHASE (authorise + capture in one).
     *
     * @return array{session_id: string, success_indicator: ?string}
     */
    public function initiateCheckout(string $orderId, float $amount, string $description, string $returnUrl, string $cancelUrl, string $locale = 'en'): array
    {
        $body = [
            'apiOperation' => 'INITIATE_CHECKOUT',
            'interaction' => [
                'operation' => 'PURCHASE',
                'returnUrl' => $returnUrl,
                'cancelUrl' => $cancelUrl,
                // Declined three times, the parent comes back to us rather than being left on the gateway's page.
                'retryAttemptCount' => 3,
                'redirectMerchantUrl' => $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'final=1',
                'timeout' => self::PAGE_TIMEOUT_SECONDS,
                'timeoutUrl' => $cancelUrl,
                'locale' => $locale === 'ar' ? 'ar' : 'en',
                'merchant' => ['name' => mb_substr((string) $this->settings->merchantName, 0, 40)],
                'displayControl' => ['billingAddress' => 'HIDE', 'customerEmail' => 'HIDE', 'shipping' => 'HIDE'],
            ],
            'order' => [
                'id' => $orderId,
                'reference' => $orderId,
                'amount' => self::amount($amount),
                'currency' => self::CURRENCY,
                'description' => mb_substr($description, 0, 127),
            ],
        ];

        $response = $this->send('POST', '/session', $body, self::LOG_CHECKOUT);
        if (($response['result'] ?? null) !== 'SUCCESS' || blank($response['session']['id'] ?? null)) {
            throw new MpgsException('The card payment page could not be opened. Please try again.');
        }

        return [
            'session_id' => (string) $response['session']['id'],
            'success_indicator' => $response['successIndicator'] ?? null,
        ];
    }

    /**
     * RETRIEVE_ORDER: the gateway's own record of an order, or null when it has none —
     * the parent never submitted a card on the page. Safe to repeat.
     */
    public function retrieveOrder(string $orderId): ?array
    {
        try {
            return $this->send('GET', '/order/'.rawurlencode($orderId), null, self::LOG_RETRIEVE, retry: true);
        } catch (MpgsException $e) {
            if ($e->getCode() === 404 || ($e->getCode() === 400 && self::isUnknownOrder($e->getMessage()))) {
                return null;
            }

            throw $e;
        }
    }

    /** REFUND of captured funds on an order, as a new transaction on it. */
    public function refund(string $orderId, string $transactionId, float $amount): array
    {
        return $this->send('PUT', '/order/'.rawurlencode($orderId).'/transaction/'.rawurlencode($transactionId), [
            'apiOperation' => 'REFUND',
            'transaction' => ['amount' => self::amount($amount), 'currency' => self::CURRENCY],
        ], self::LOG_REFUND);
    }

    /**
     * The order's latest money transaction (the payment or its decline), skipping
     * the AUTHENTICATION rows 3-D Secure adds.
     */
    public static function lastPayment(array $order): ?array
    {
        $transactions = array_values(array_filter(
            (array) ($order['transaction'] ?? []),
            fn ($row) => is_array($row) && ! str_starts_with((string) ($row['transaction']['type'] ?? ''), 'AUTHENTICATION'),
        ));

        return $transactions ? end($transactions) : null;
    }

    /** A decline in words a parent understands. */
    public static function describe(?string $gatewayCode): string
    {
        return match ((string) $gatewayCode) {
            'DECLINED', 'DECLINED_DO_NOT_CONTACT', 'DECLINED_INVALID_PIN', 'DECLINED_PIN_REQUIRED', 'REFERRED' => 'Your bank declined the payment.',
            'INSUFFICIENT_FUNDS' => 'The card does not have enough funds.',
            'EXPIRED_CARD' => 'The card has expired.',
            'INVALID_CSC' => 'The card security code (CVV) was wrong.',
            'DECLINED_AVS', 'DECLINED_AVS_CSC', 'DECLINED_CSC' => 'The card details did not match.',
            'AUTHENTICATION_FAILED', 'AUTHENTICATION_IN_PROGRESS' => 'The card could not be verified with your bank.',
            'TIMED_OUT', 'ACQUIRER_SYSTEM_ERROR', 'SYSTEM_ERROR' => 'The bank did not answer in time.',
            'BLOCKED' => 'The payment was blocked.',
            'CANCELLED' => 'The payment was cancelled.',
            default => 'The payment was not completed.',
        };
    }

    private static function isUnknownOrder(string $explanation): bool
    {
        return (bool) preg_match('/(unable to find|not found|no such|does not exist).*order|order.*(not found|does not exist)/i', $explanation);
    }

    private function send(string $method, string $path, ?array $body, string $service, bool $retry = false): array
    {
        $url = $this->settings->merchantUrl().$path;
        $log = $this->startLog($service, $method, $url, $body);

        try {
            $response = $this->request($retry)->send($method, $url, $body === null ? [] : ['json' => $body]);
        } catch (ConnectionException $e) {
            Log::warning('MPGS unreachable', ['service' => $service, 'error' => $e->getMessage()]);
            $this->finishLog($log, 'failed', null, 'The gateway could not be reached: '.$e->getMessage());

            throw new MpgsException('The card payment service could not be reached. Please try again.', 0, $e);
        }

        $json = $response->json() ?? [];

        if ($response->successful()) {
            $failed = in_array($json['result'] ?? null, ['FAILURE', 'ERROR'], true);
            $this->finishLog($log, $failed ? 'failed' : 'success', $json, $failed ? $this->explain($json, $response) : null);

            return $json;
        }

        $explanation = $this->explain($json, $response);
        $this->finishLog($log, 'failed', $json ?: ['http_status' => $response->status(), 'body' => $response->body()], $explanation);

        if ($response->status() === 401) {
            Log::error('MPGS rejected the merchant credentials', ['service' => $service]);

            throw new MpgsException('The card payment service rejected the merchant ID or API password.', 401);
        }

        if ($response->serverError()) {
            Log::error('MPGS request failed', ['service' => $service, 'status' => $response->status(), 'body' => $response->body()]);
        }

        throw new MpgsException($explanation, $response->status());
    }

    private function request(bool $retry): PendingRequest
    {
        $request = Http::withBasicAuth('merchant.'.$this->settings->merchantId, (string) $this->settings->apiPassword)
            ->acceptJson()
            ->timeout(30);

        // Only reads are retried: a timed-out write may already have happened.
        return $retry ? $request->retry(2, 300, fn ($exception) => $exception instanceof ConnectionException, throw: false) : $request;
    }

    /** "INVALID_REQUEST: explanation (field)" from the gateway's error object. */
    private function explain(array $json, Response $response): string
    {
        $error = $json['error'] ?? [];
        $parts = array_filter([$error['cause'] ?? null, $error['explanation'] ?? null]);
        $text = $parts ? implode(': ', $parts) : 'HTTP '.$response->status();

        return filled($error['field'] ?? null) ? $text.' ('.$error['field'].')' : $text;
    }

    private function startLog(string $service, string $method, string $url, ?array $body): ?ApiLog
    {
        try {
            $actor = $this->actor();

            return ApiLog::create([
                'endpoint' => $url,
                'method' => $method,
                'service_name' => $service,
                'request' => $body === null ? null : json_encode($body, JSON_UNESCAPED_SLASHES),
                'status' => 'pending',
                'username' => $this->settings->merchantId,
                'user_id' => $actor instanceof User ? $actor->id : null,
                'user_name' => $actor?->name,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function finishLog(?ApiLog $log, string $status, ?array $response, ?string $description): void
    {
        if (! $log) {
            return;
        }

        try {
            $log->update([
                'status' => $status,
                'response' => $response === null ? null : json_encode($response, JSON_UNESCAPED_SLASHES),
                'description' => $description,
            ]);
        } catch (\Throwable) {
            // Logging must never mask the real outcome of the payment.
        }
    }

    /** Staff user, or the signed-in parent (named, not linked: user_id points at users). */
    private function actor(): ?Authenticatable
    {
        $parent = Auth::guard('parent');

        return Auth::user() ?? ($parent->hasUser() ? $parent->user() : null);
    }
}
