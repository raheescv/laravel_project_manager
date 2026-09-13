<?php

namespace App\Services\Payment;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the Tap Payments Charges API.
 *
 * Built per tenant with that tenant's secret key — there is no platform-wide Tap
 * account. Only the two calls the hosted-page flow needs are exposed.
 *
 * @see https://developers.tap.company/reference/create-a-charge
 * @see https://developers.tap.company/reference/retrieve-a-charges
 */
class TapClient
{
    public function __construct(private readonly string $secretKey) {}

    /**
     * POST /charges. With `source.id = src_all` the returned charge is INITIATED
     * and `transaction.url` is Tap's hosted payment page.
     *
     * Deliberately not retried: a timeout after Tap accepted the request would
     * leave a second, orphaned charge behind.
     */
    public function createCharge(array $payload): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('charges', $payload), 'create charge');
    }

    /** GET /charges/{id} — the authoritative state of a charge. Safe to retry. */
    public function retrieveCharge(string $chargeId): array
    {
        return $this->send(
            fn (PendingRequest $http) => $http
                ->retry(2, 300, fn ($exception) => $exception instanceof ConnectionException, throw: false)
                ->get('charges/'.rawurlencode($chargeId)),
            'retrieve charge',
        );
    }

    /** @param  callable(PendingRequest): Response  $call */
    private function send(callable $call, string $operation): array
    {
        try {
            $response = $call($this->request());
        } catch (ConnectionException $e) {
            Log::warning('Tap API unreachable', ['operation' => $operation, 'error' => $e->getMessage()]);

            throw new TapException('The payment service could not be reached. Please try again.', 0, $e);
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error('Tap API request failed', [
            'operation' => $operation,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $description = $response->json('errors.0.description') ?? $response->json('message');

        throw new TapException(is_string($description) && $description !== '' ? $description : 'The payment service rejected the request.', $response->status());
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.tap.base_url'))
            ->withToken($this->secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }
}
