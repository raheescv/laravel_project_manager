<?php

namespace App\Http\Resources\V1\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'branch' => $this->branch?->name,
            'customer' => [
                'name' => $this->customer_name ?: $this->account?->name,
                'mobile' => $this->customer_mobile ?: $this->account?->mobile,
            ],
            'items' => $this->items->map(fn ($item) => [
                // Edit round-trip ids — the app sends these back so an update
                // patches the existing rows instead of creating duplicates.
                'id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'employee_id' => $item->employee_id ? (int) $item->employee_id : null,
                'code' => $item->product?->code,
                'name' => $item->product?->name,
                'name_arabic' => $item->product?->name_arabic,
                'type' => $item->product?->type,
                'employee' => $item->employee?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax' => (float) $item->tax,
                'total' => round((float) $item->unit_price * (float) $item->quantity - (float) $item->discount, 2),
            ])->values(),
            'payments' => $this->payments->map(fn ($payment) => [
                'id' => (int) $payment->id,
                'payment_method_id' => $payment->payment_method_id ? (int) $payment->payment_method_id : null,
                'method' => $payment->paymentMethod?->name,
                'amount' => (float) $payment->amount,
            ])->values(),
            'summary' => [
                'gross_amount' => (float) $this->gross_amount,
                'item_discount' => (float) $this->item_discount,
                'other_discount' => (float) $this->other_discount,
                'tax_amount' => (float) $this->tax_amount,
                'grand_total' => (float) $this->grand_total,
                'paid' => (float) $this->paid,
                'balance' => (float) $this->balance,
            ],
            'created_by' => $this->createdUser?->name,
        ];
    }
}
