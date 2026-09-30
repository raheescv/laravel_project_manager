<?php

namespace App\Actions\V1\Storefront;

use App\Exceptions\StorefrontCheckoutException;
use App\Models\StorefrontCheckout;
use App\Services\Payment\TapClient;
use App\Services\Payment\TapException;
use App\Services\TenantService;
use App\Support\Storefront\TapSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RefundCheckoutAction
{
    /**
     * Send the full captured amount of a checkout back to the customer through Tap.
     *
     * Only one refund request per checkout can be in flight: a cache lock covers
     * the HTTP call, and a checkout whose refund is pending or done is refused.
     * Tap's reply — which may still be PENDING — is recorded by SyncRefundAction,
     * which also cancels the sale once the refund is REFUNDED.
     *
     * @throws StorefrontCheckoutException when the checkout cannot be refunded
     * @throws \App\Services\Payment\TapException when Tap cannot be reached or refuses
     */
    public function execute(StorefrontCheckout $checkout, string $reason, int $userId): StorefrontCheckout
    {
        $lock = Cache::lock('storefront-refund:'.$checkout->id, 30);

        if (! $lock->get()) {
            throw new StorefrontCheckoutException('A refund for this payment is already being sent.');
        }

        try {
            $checkout = StorefrontCheckout::query()->whereKey($checkout->id)->firstOrFail();

            if (! $checkout->isRefundable()) {
                throw new StorefrontCheckoutException('Only a captured payment that has not been refunded can be refunded.');
            }

            $settings = TapSettings::current();

            if (! $settings->secretKey) {
                throw new StorefrontCheckoutException('Online payment is not configured for this store.');
            }

            $reason = Str::limit(trim($reason), 250, '');
            $payload = $this->payload($checkout, $reason);

            try {
                $refund = (new TapClient($settings->secretKey))->createRefund($payload);
            } catch (TapException $e) {
                // Kept so the details popup shows what was sent and why Tap refused it.
                $checkout->update([
                    'refund_request' => $payload,
                    'refund_response' => ['error' => $e->getMessage(), 'http_status' => $e->getCode()],
                ]);

                throw $e;
            }

            return (new SyncRefundAction())->apply($checkout, $refund, [
                'refund_request' => $payload,
                'refund_amount' => $checkout->amount,
                'refund_reason' => $reason !== '' ? $reason : null,
                'refund_requested_by' => $userId,
                'refund_requested_at' => now(),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * @see https://developers.tap.company/reference/create-a-refund
     *
     * @return array<string, mixed>
     */
    private function payload(StorefrontCheckout $checkout, string $reason): array
    {
        $subdomain = app(TenantService::class)->getCurrentTenant()?->subdomain;

        return [
            'charge_id' => $checkout->gateway_charge_id,
            'amount' => (float) $checkout->amount,
            'currency' => $checkout->currency,
            'reason' => $reason !== '' ? $reason : 'requested_by_customer',
            'description' => Str::limit('Refund of online order '.$checkout->reference, 250, ''),
            'reference' => ['merchant' => $checkout->reference],
            'metadata' => ['checkout' => $checkout->reference],
            // Tap reports the refund's outcome to the same webhook as the charge's.
            'post' => ['url' => route('api.v1.storefront.checkout.webhook', array_filter(['tenant' => $subdomain]))],
        ];
    }
}
