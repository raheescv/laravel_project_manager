<?php

namespace App\Http\Resources\V1\Technician;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The full complaint-detail payload for the technician workflow screen.
 *
 * Assembles exactly what App\Livewire\Maintenance\Complaint::loadData() puts on
 * the page: property/customer info bars, activity log, sibling complaints, and
 * the supply request (items, notes, images). Status/priority use the enum
 * label()/color() so the app renders identical chips.
 */
class ComplaintDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mc = $this->resource;
        $maintenance = $mc->maintenance;
        $property = $maintenance?->property;
        $rentOut = $maintenance?->rentOut;
        $customer = $maintenance?->customer ?? $rentOut?->customer;
        $supplyRequest = $mc->supplyRequest;

        return [
            'id' => $mc->id,
            'status' => $mc->status?->value ?? 'pending',
            'status_label' => $mc->status?->label() ?? 'Pending',
            'status_color' => $mc->status?->color() ?? 'warning',
            'is_completed' => $mc->status?->value === 'completed',
            'is_cancelled' => $mc->status?->value === 'cancelled',
            'technician_remark' => $mc->technician_remark ?? '',

            'property_info' => [
                'registration_id' => $maintenance?->id,
                'group' => $property?->building?->group?->name ?? '',
                'building' => $property?->building?->name ?? '',
                'type' => $property?->type?->name ?? '',
                'property_number' => $property?->number ?? '',
                'priority' => $maintenance?->priority?->label() ?? '',
                'priority_color' => $maintenance?->priority?->color() ?? 'secondary',
                'segment' => $maintenance?->segment?->label() ?? '',
                'segment_color' => $maintenance?->segment?->color() ?? 'secondary',
                'date' => $maintenance?->date?->format('d-m-Y') ?? '',
                'time' => $maintenance?->time ?? '',
            ],

            'customer_info' => [
                'complaint_status' => $mc->status?->label() ?? 'Pending',
                'complaint_status_color' => $mc->status?->color() ?? 'warning',
                'rentout_id' => $rentOut?->id ?? '',
                'rentout_status' => $rentOut?->status?->label() ?? '',
                'agreement_start_date' => $rentOut?->start_date?->format('d-m-Y') ?? '',
                'customer_name' => $customer?->name ?? '',
                'customer_mobile' => $customer?->mobile ?? $maintenance?->contact_no ?? '',
                'work_order_no' => $supplyRequest ? ($supplyRequest->order_no ?? $supplyRequest->id) : 'Not assigned',
            ],

            'activity_log' => [
                'created_by' => $mc->creator?->name ?? '',
                'created_at' => $mc->created_at?->format('d-m-Y h:i:s A') ?? '',
                'assigned_by' => $mc->assignedBy?->name ?? '',
                'assigned_at' => $mc->assigned_at?->format('d-m-Y h:i:s A') ?? '',
                'completed_by' => $mc->completedBy?->name ?? '',
                'completed_at' => $mc->completed_at?->format('d-m-Y h:i:s A') ?? '',
            ],

            // Only the sibling complaints assigned to this technician — other
            // technicians' jobs on the same maintenance aren't surfaced here.
            'all_complaints' => $maintenance?->maintenanceComplaints
                ->where('technician_id', $mc->technician_id)
                ->map(fn ($item) => [
                'id' => $item->id,
                'category_name' => $item->complaint?->category?->name ?? '',
                'complaint_name' => $item->complaint?->name ?? '',
                'technician_name' => $item->technician?->name ?? '',
                'technician_remark' => $item->technician_remark ?? '',
                'status' => $item->status?->value ?? 'pending',
                'status_label' => $item->status?->label() ?? 'Pending',
                'status_color' => $item->status?->color() ?? 'warning',
                'is_current' => $item->id === $mc->id,
            ])->values() ?? [],

            'supply_request' => [
                'id' => $supplyRequest?->id,
                'total' => (float) ($supplyRequest?->total ?? 0),
                'other_charges' => (float) ($supplyRequest?->other_charges ?? 0),
                'grand_total' => (float) ($supplyRequest?->grand_total ?? 0),
                'items' => $supplyRequest ? $supplyRequest->items->map(fn ($item) => [
                    'id' => $item->id,
                    'branch_id' => $item->branch_id,
                    'branch_name' => $item->branch?->name ?? 'Main Store',
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? '',
                    'mode' => $item->mode,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                    'remarks' => $item->remarks ?? '',
                ])->values() : [],
                'notes' => $supplyRequest ? $supplyRequest->notes->map(fn ($note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'creator' => $note->creator?->name ?? '',
                    'created_at' => $note->created_at?->format('d-m-Y h:i:s A'),
                ])->values() : [],
                'images' => $supplyRequest ? $supplyRequest->images->map(fn ($image) => [
                    'id' => $image->id,
                    'name' => $image->name,
                    'type' => $image->type,
                    'path' => asset($image->path),
                    'is_image' => Str::contains($image->type ?? '', 'image'),
                    'is_video' => $image->is_video,
                    'is_pdf' => $image->is_pdf,
                ])->values() : [],
            ],
        ];
    }
}
