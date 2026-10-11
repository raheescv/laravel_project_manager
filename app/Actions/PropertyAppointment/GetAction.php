<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;
use Illuminate\Support\Str;

/**
 * The appointments list query, shared by the on-screen table and its Excel
 * and PDF exports so a download always holds exactly the rows on screen.
 */
class GetAction
{
    /** Columns the list may be ordered by; anything else falls back to the schedule. */
    public const SORTABLE = ['property_appointments.scheduled_at', 'property_appointments.id'];

    public function execute(array $data): array
    {
        $sortField = in_array($data['sort_field'] ?? '', self::SORTABLE, true) ? $data['sort_field'] : 'property_appointments.scheduled_at';
        $sortDirection = ($data['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $list = PropertyAppointment::query()
            ->with([
                'customer:id,name,mobile',
                'employee:id,name',
                'rentOut:id,property_id',
                'rentOut.property:id,number,property_building_id',
                'rentOut.property.building:id,name',
            ])
            ->when($data['search'] ?? '', function ($query, $value) {
                // The reference in the list is derived (VW-<year>-<padded id>), so read
                // the id out of whatever was typed: "14", "0014" and "VW-2026-0014" all
                // point at appointment 14. A padded string can never LIKE-match the id.
                $id = (int) Str::afterLast($value, '-');

                $query->where(function ($query) use ($value, $id) {
                    $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('rentOut.property', fn ($q) => $q->where('number', 'like', "%{$value}%"))
                        ->when($id, fn ($q) => $q->orWhere('property_appointments.id', $id));
                });
            })
            ->when($data['status'] ?? '', fn ($query, $value) => $query->where('property_appointments.status', $value))
            ->when($data['employee_id'] ?? '', fn ($query, $value) => $query->where('property_appointments.employee_id', $value))
            ->when($data['branch_id'] ?? '', fn ($query, $value) => $query->where('property_appointments.branch_id', $value))
            ->when($data['from_date'] ?? '', fn ($query, $value) => $query->whereDate('property_appointments.scheduled_at', '>=', $value))
            ->when($data['to_date'] ?? '', fn ($query, $value) => $query->whereDate('property_appointments.scheduled_at', '<=', $value))
            ->orderBy($sortField, $sortDirection);

        return ['list' => $list];
    }
}
