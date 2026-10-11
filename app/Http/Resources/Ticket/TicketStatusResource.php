<?php

namespace App\Http\Resources\Ticket;

use App\Models\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketStatus
 */
class TicketStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
        ];
    }
}
