<?php

namespace App\Http\Resources\V1\Day;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DaySessionResource extends JsonResource
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
            'branch' => $this->branch?->name,
            'status' => $this->status,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'opened_by' => $this->opener?->name,
            'closed_by' => $this->closer?->name,
            'opening_amount' => (float) $this->opening_amount,
            'closing_amount' => $this->closing_amount !== null ? (float) $this->closing_amount : null,
            'expected_amount' => $this->expected_amount !== null ? (float) $this->expected_amount : null,
        ];
    }
}
