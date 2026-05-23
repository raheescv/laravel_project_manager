<?php

namespace App\Http\Resources\V1\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'invoice_no' => $this->invoice_no,
            'reference_no' => $this->reference_no,
            'date' => $this->date,
            'status' => $this->status,
            'sale_type' => $this->sale_type,
            'branch' => $this->branch?->name,
            'customer' => [
                'name' => $this->customer_name ?: $this->account?->name,
                'mobile' => $this->customer_mobile ?: $this->account?->mobile,
            ],
            'items_count' => (int) ($this->items_count ?? $this->items?->count() ?? 0),
            'summary' => [
                'gross_amount' => (float) $this->gross_amount,
                'item_discount' => (float) $this->item_discount,
                'other_discount' => (float) $this->other_discount,
                'tax_amount' => (float) $this->tax_amount,
                'paid' => (float) $this->paid,
            ],
            'payment_methods' => $this->payment_method_name,
            'created_by' => $this->createdUser?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
