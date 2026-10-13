<?php

namespace App\Http\Resources\ProjectContract;

use App\Models\ProjectContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One contract in the list. Expects `paid_total` and `next_due_*` selected by
 * the list query.
 *
 * @mixin ProjectContract
 */
class ContractRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $amount = (float) $this->contract_amount;
        $paid = (float) ($this->paid_total ?? 0);

        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'project_name' => $this->project_name,
            'vendor' => $this->vendor ? ['id' => $this->vendor->id, 'name' => $this->vendor->name] : null,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'contract_amount' => $amount,
            'paid' => $paid,
            'balance' => round($amount - $paid, 2),
            'next_due_date' => $this->next_due_date,
            'next_due_amount' => $this->next_due_amount !== null ? (float) $this->next_due_amount : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'pending_level' => $this->pending_level !== null ? (int) $this->pending_level : null,
            'level_count' => $this->level_count !== null ? (int) $this->level_count : null,
        ];
    }
}
