<?php

namespace App\Http\Resources\V1\SaleReturn;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A sale presented for return: each line exposes the source `sale_item_id` and
 * the remaining returnable quantity so the app can cap the qty steppers. The
 * `returned_quantity` / `returnable_quantity` attributes are populated by
 * V1\SaleReturn\ReturnableSaleAction.
 */
class ReturnableSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sale_id' => (string) $this->id,
            'invoice_no' => $this->invoice_no,
            'reference_no' => $this->reference_no,
            'date' => $this->date,
            'status' => $this->status,
            'branch' => $this->branch?->name,
            'account_id' => $this->account_id,
            'customer' => [
                'name' => $this->customer_name ?: $this->account?->name,
                'mobile' => $this->customer_mobile ?: $this->account?->mobile,
            ],
            'items' => $this->items->map(fn ($item) => [
                'sale_item_id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'inventory_id' => (int) $item->inventory_id,
                'unit_id' => (int) ($item->unit_id ?: 1),
                'conversion_factor' => (float) ($item->conversion_factor ?: 1),
                'name' => $item->product?->name,
                'name_arabic' => $item->product?->name_arabic,
                'type' => $item->product?->type,
                'employee' => $item->employee?->name,
                'employee_id' => $item->employee_id ? (int) $item->employee_id : null,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax' => (float) $item->tax,
                'sold_quantity' => (float) $item->quantity,
                'returned_quantity' => (float) ($item->returned_quantity ?? 0),
                'returnable_quantity' => (float) ($item->returnable_quantity ?? $item->quantity),
            ])->values(),
        ];
    }
}
