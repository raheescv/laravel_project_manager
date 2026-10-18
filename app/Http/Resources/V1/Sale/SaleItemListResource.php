<?php

namespace App\Http\Resources\V1\Sale;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One sold line on the mobile Sales list's Items tab.
 *
 * @mixin SaleItem
 */
class SaleItemListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sale = $this->sale;

        return [
            'id' => (string) $this->id,
            'sale_id' => (string) $this->sale_id,
            'invoice_no' => $sale?->invoice_no,
            'date' => $sale?->date,
            'status' => $sale?->status,
            'customer' => $sale?->customer_name ?: $sale?->account?->name,
            'created_by' => $sale?->createdUser?->name,
            'product' => [
                'id' => (string) $this->product_id,
                'name' => $this->product?->name,
                'code' => $this->product?->code,
            ],
            'unit' => $this->unit?->name,
            'employee' => $this->employee?->name,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
        ];
    }
}
