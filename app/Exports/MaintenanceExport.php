<?php

namespace App\Exports;

use App\Models\Maintenance;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaintenanceExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return Maintenance::with(['property', 'building.group', 'customer', 'creator'])
            ->withCount('maintenanceComplaints')
            ->when($this->filters['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('contact_no', 'like', "%{$value}%")
                        ->orWhere('remark', 'like', "%{$value}%");
                });
            })
            ->when($this->filters['filterStatus'] ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->filters['filterPriority'] ?? '', function ($query, $value) {
                return $query->where('priority', $value);
            })
            ->when($this->filters['filterSegment'] ?? '', function ($query, $value) {
                return $query->where('segment', $value);
            })
            ->when($this->filters['filterProperty'] ?? '', function ($query, $value) {
                return $query->where('property_id', $value);
            })
            ->when($this->filters['filterBuilding'] ?? '', function ($query, $value) {
                return $query->where('property_building_id', $value);
            })
            ->when($this->filters['filterGroup'] ?? '', function ($query, $value) {
                return $query->where('property_group_id', $value);
            })
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                return $query->where('date', '>=', $value);
            })
            ->when($this->filters['to_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<=', $value);
            })
            ->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Property',
            'Building',
            'Group',
            'Customer',
            'Priority',
            'Segment',
            'Complaints',
            'Status',
            'Contact No',
            'Created By',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->date?->format('Y-m-d'),
            $row->property?->name ?? '',
            $row->building?->name ?? '',
            $row->building?->group?->name ?? '',
            $row->customer?->name ?? '',
            $row->priority?->label() ?? '',
            $row->segment?->label() ?? '',
            $row->maintenance_complaints_count,
            $row->status?->label() ?? '',
            $row->contact_no ?? '',
            $row->creator?->name ?? '',
        ];
    }
}
