<?php

namespace App\Http\Resources\V1\Storefront;

use App\Models\StorefrontCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the storefront may know about a checkout. Customer details are never
 * echoed back — the reference is all it takes to read this.
 *
 * @mixin StorefrontCheckout
 */
class CheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            // pending | paid | failed | review (paid, but staff must finish the order)
            'status' => $this->status,
            // Tap's own word for the charge (CAPTURED, DECLINED, ABANDONED…).
            'gateway_status' => $this->gateway_status,
            'fulfilment' => $this->fulfilment,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            // Only while the customer still has to pay; a settled checkout never hands out a payment link.
            'payment_url' => $this->status === StorefrontCheckout::STATUS_PENDING
                ? data_get($this->gateway_response, 'transaction.url')
                : null,
            'invoice_no' => $this->sale?->invoice_no,
            'branch' => $this->branch ? ['id' => $this->branch->id, 'name' => $this->branch->name] : null,
        ];
    }
}
