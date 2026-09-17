<?php

namespace App\Http\Resources\V1\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One bill with its lines and how it was paid. @mixin \App\Models\Sale */
class BillDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'date' => $this->date,
            'created_at' => $this->created_at?->toIso8601String(),
            'branch' => $this->branch?->name,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product?->name,
                'unit' => $item->unit?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => round((float) $item->unit_price, 2),
                'total' => round((float) $item->total, 2),
            ])->values(),
            'discount' => round((float) $this->item_discount + (float) $this->other_discount, 2),
            'tax' => round((float) $this->tax_amount, 2),
            'grand_total' => round((float) $this->grand_total, 2),
            'payments' => $this->payments->map(fn ($payment) => [
                'method' => $payment->paymentMethod?->name,
                'amount' => round((float) $payment->amount, 2),
            ])->values(),
        ];
    }
}
