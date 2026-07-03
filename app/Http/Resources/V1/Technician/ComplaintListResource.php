<?php

namespace App\Http\Resources\V1\Technician;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single assigned-complaint row for the technician list.
 *
 * Mirrors the columns the web technician grid selects
 * (App\Livewire\Maintenance\Technician::query) so the app card renders the
 * same complaint + category, property, priority, status, appointment and
 * customer.
 */
class ComplaintListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $maintenance = $this->maintenance;
        $property = $maintenance?->property;

        return [
            'id' => $this->id,
            'registration_id' => $maintenance?->id,
            'status' => $this->status?->value ?? 'pending',
            'status_label' => $this->status?->label() ?? 'Pending',
            'status_color' => $this->status?->color() ?? 'warning',
            'complaint_name' => $this->complaint?->name ?? '',
            'category_name' => $this->complaint?->category?->name ?? '',
            'technician_remark' => $this->technician_remark ?? '',
            'property_number' => $property?->number ?? '',
            'building' => $property?->building?->name ?? '',
            'group' => $property?->building?->group?->name ?? '',
            'priority' => $maintenance?->priority?->value ?? '',
            'priority_label' => $maintenance?->priority?->label() ?? '',
            'priority_color' => $maintenance?->priority?->color() ?? 'secondary',
            'date' => $maintenance?->date?->format('Y-m-d') ?? '',
            'time' => $maintenance?->time ?? '',
            'customer_name' => $maintenance?->customer?->name
                ?? $maintenance?->rentOut?->customer?->name
                ?? '',
            'customer_mobile' => $maintenance?->customer?->mobile
                ?? $maintenance?->rentOut?->customer?->mobile
                ?? $maintenance?->contact_no
                ?? '',
        ];
    }
}
