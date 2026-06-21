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
            // The source sale this return is raised against — the app needs it to
            // re-fetch the returnable lines when editing.
            'sale_id' => $this->sale_id ? (string) $this->sale_id : null,
            'account_id' => $this->account_id ? (int) $this->account_id : null,
            'date' => $this->date,
            'status' => $this->status,
            'branch' => $this->branch?->name,
            'customer' => [
                'name' => $this->account?->name,
                'mobile' => $this->account?->mobile,
            ],
            'items' => $this->items->map(fn ($item) => [
                // Edit round-trip ids — id = sale_return_item id; sale_item_id is
                // the source sale line the return caps its quantity against.
                'id' => (int) $item->id,
                'sale_item_id' => $item->sale_item_id ? (int) $item->sale_item_id : null,
                'product_id' => (int) $item->product_id,
                'employee_id' => $item->employee_id ? (int) $item->employee_id : null,
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
                'total' => (float) $this->total,
                'grand_total' => (float) $this->grand_total,
                'paid' => (float) $this->paid,
                'balance' => (float) $this->balance,
            ],
            'created_by' => $this->createdUser?->name,
        ];
    }
}
