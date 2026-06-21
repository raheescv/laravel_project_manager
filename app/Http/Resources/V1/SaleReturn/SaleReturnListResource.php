<?php

namespace App\Http\Resources\V1\SaleReturn;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Mirrors V1\Sale\SaleListResource. Sale returns have no invoice_no /
     * customer_name / payment_method_name columns, so those are derived from the
     * account and the (eager-loaded) payments relation.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            // The Sales list keys on `invoice_no`; expose reference_no under both
            // keys so the shared row widget can render either flow unchanged.
            'invoice_no' => $this->reference_no,
            'reference_no' => $this->reference_no,
            'date' => $this->date,
            'status' => $this->status,
            'branch' => $this->branch?->name,
            'customer' => [
                'name' => $this->account?->name,
                'mobile' => $this->account?->mobile,
            ],
            'items_count' => (int) ($this->items_count ?? $this->items?->count() ?? 0),
            'summary' => [
                'gross_amount' => (float) $this->gross_amount,
                'item_discount' => (float) $this->item_discount,
                'other_discount' => (float) $this->other_discount,
                'tax_amount' => (float) $this->tax_amount,
                'total' => (float) $this->total,
                'grand_total' => (float) $this->grand_total,
                'paid' => (float) $this->paid,
            ],
            'payment_methods' => $this->whenLoaded('payments', fn () => $this->payments
                ->map(fn ($payment) => $payment->paymentMethod?->name)
                ->filter()
                ->unique()
                ->implode(', ')),
            'created_by' => $this->createdUser?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
