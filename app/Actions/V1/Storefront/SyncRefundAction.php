<?php

namespace App\Actions\V1\Storefront;

use App\Actions\Sale\UpdateAction as SaleUpdateAction;
use App\Exceptions\StorefrontCheckoutException;
use App\Models\Sale;
use App\Models\StorefrontCheckout;
use App\Services\Payment\TapClient;
use App\Support\Storefront\TapSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncRefundAction
{
    /**
     * Bring a checkout's refund in line with what Tap reports for it.
     *
     * Runs from the row's Check button and from Tap's webhook. Like the charge
     * sync, the outcome is always read from Tap with the secret key, never taken
     * from a webhook body.
     *
     * @throws \App\Services\Payment\TapException when Tap cannot be asked
     */
    public function execute(StorefrontCheckout $checkout): StorefrontCheckout
    {
        if ($checkout->status === StorefrontCheckout::STATUS_REFUNDED || ! $checkout->refund_id) {
            return $checkout;
        }

        $settings = TapSettings::current();

        if (! $settings->secretKey) {
            throw new StorefrontCheckoutException('Online payment is not configured for this store.');
        }

        $refund = (new TapClient($settings->secretKey))->retrieveRefund($checkout->refund_id);

        return $this->apply($checkout, $refund);
    }

    /**
     * Record Tap's refund reply on the checkout and, once Tap reports it REFUNDED,
     * mark the checkout refunded and cancel the sale it recorded.
     *
     * [$attributes] are the request's own facts (reason, who asked, when) and are
     * only passed when the refund was just created; a later sync must match the
     * refund id already stored.
     *
     * @param  array<string, mixed>  $refund  Tap's refund object
     * @param  array<string, mixed>  $attributes
     */
    public function apply(StorefrontCheckout $checkout, array $refund, array $attributes = []): StorefrontCheckout
    {
        return DB::transaction(function () use ($checkout, $refund, $attributes) {
            $locked = StorefrontCheckout::query()->whereKey($checkout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === StorefrontCheckout::STATUS_REFUNDED) {
                return $locked;
            }

            $refundId = $refund['id'] ?? null;
            if (! $refundId || ($attributes === [] && $refundId !== $locked->refund_id)) {
                throw new StorefrontCheckoutException('The payment service returned a different refund.');
            }

            $status = strtoupper((string) ($refund['status'] ?? 'UNKNOWN'));
            $locked->fill([
                ...$attributes,
                'refund_id' => $refundId,
                'refund_status' => $status,
                'refund_response' => $refund,
            ]);

            if ($status === 'REFUNDED') {
                $this->settle($locked);
            }
            // PENDING / IN_PROGRESS: Tap is still sending the money back.
            // FAILED / CANCELLED…: the checkout keeps its status and may be refunded again.

            $locked->save();

            return $locked;
        });
    }

    /**
     * The money is back with the customer: the order no longer stands, so its sale
     * is cancelled — stock returned and its journal reversed — exactly as a
     * cancellation from the sale screen does.
     *
     * A sale that cannot be cancelled must not hide that the refund happened: its
     * writes roll back to a savepoint and the reason is kept on the checkout, for
     * staff to cancel the sale by hand.
     */
    private function settle(StorefrontCheckout $checkout): void
    {
        $checkout->fill([
            'status' => StorefrontCheckout::STATUS_REFUNDED,
            'refunded_at' => now(),
            'failure_reason' => null,
        ]);

        if (! $checkout->sale_id) {
            return;
        }

        try {
            DB::transaction(fn () => $this->cancelSale($checkout));
        } catch (\Throwable $e) {
            Log::error('Storefront checkout refunded but its sale was not cancelled', [
                'checkout' => $checkout->reference,
                'refund' => $checkout->refund_id,
                'sale_id' => $checkout->sale_id,
                'reason' => $e->getMessage(),
            ]);

            $checkout->failure_reason = 'Refunded through Tap, but the sale could not be cancelled: '.$e->getMessage();
        }
    }

    private function cancelSale(StorefrontCheckout $checkout): void
    {
        $sale = Sale::withoutGlobalScopes()->where('tenant_id', $checkout->tenant_id)->whereKey($checkout->sale_id)->first();

        if (! $sale || $sale->status === 'cancelled') {
            return;
        }

        $userId = $checkout->refund_requested_by ?: TapSettings::current()->userId;
        if (! $userId) {
            throw new RuntimeException('No user is set to record online sales.');
        }

        $response = (new SaleUpdateAction())->execute(['status' => 'cancelled'], $sale->id, $userId);

        if (! $response['success']) {
            throw new RuntimeException($response['message']);
        }
    }
}
