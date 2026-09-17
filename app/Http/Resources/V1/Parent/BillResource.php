<?php

namespace App\Http\Resources\V1\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A row of a student's bills. @mixin \App\Models\Sale */
class BillResource extends JsonResource
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
            'items_count' => (int) $this->items_count,
            'grand_total' => round((float) $this->grand_total, 2),
        ];
    }
}
