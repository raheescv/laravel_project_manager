<?php

namespace App\Http\Resources\V1\SaleReturn;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Mirrors V1\Sale\SaleResource so the app can reuse the same receipt layout;
     * sale returns have no invoice_no, so reference_no carries the document number.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'reference_no' => $this->reference_no,
            'date' => $this->date,
            'status' => $this->status,
            'branch' => $this->branch?->name,
            'customer' => [
                'name' => $this->account?->name,
                'mobile' => $this->account?->mobile,
            ],
            'items' => $this->items->map(fn ($item) => [
                'name' => $item->product?->name,
                'name_arabic' => $item->product?->name_arabic,
                'type' => $item->product?->type,
                'employee' => $item->employee?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'total' => round((float) $item->unit_price * (float) $item->quantity - (float) $item->discount, 2),
            ])->values(),
            'payments' => $this->payments->map(fn ($payment) => [
                'method' => $payment->paymentMethod?->name,
                'amount' => (float) $payment->amount,
            ])->values(),
            'summary' => [
                'gross_amount' => (float) $this->gross_amount,
                'item_discount' => (float) $this->item_discount,
                'other_discount' => (float) $this->other_discount,
                'tax_amount' => (float) $this->tax_amount,
                'total' => (float) $this->total,
                'grand_total' => (float) $this->grand_total,
                'paid' => (float) $this->paid,
            ],
            'created_by' => $this->createdUser?->name,
        ];
    }
}
