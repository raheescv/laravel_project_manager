<?php

namespace App\Exports;

use App\Models\Property;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PropertyExport implements FromQuery, WithColumnFormatting, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return Property::with(['building.group', 'type'])
            ->when($this->filters['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $q->where('number', 'like', "%{$value}%")
                        ->orWhere('floor', 'like', "%{$value}%");
                });
            })
            ->when($this->filters['filterGroup'] ?? '', function ($query, $value) {
                return $query->where('property_group_id', $value);
            })
            ->when($this->filters['filterBuilding'] ?? '', function ($query, $value) {
                return $query->where('property_building_id', $value);
            })
            ->when($this->filters['filterType'] ?? '', function ($query, $value) {
                return $query->where('property_type_id', $value);
            })
            ->when($this->filters['filterStatus'] ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->filters['filterAvailabilityStatus'] ?? '', function ($query, $value) {
                return $query->where('availability_status', $value);
            })
            ->when($this->filters['filterFlag'] ?? '', function ($query, $value) {
                return $query->where('flag', $value);
            })
            ->when($this->filters['filterOwnership'] ?? '', function ($query, $value) {
                return $query->where('ownership', $value);
            })
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            '#',
            'Number',
            'Type',
            'Group',
            'Building',
            'Floor',
            'Rent',
            'Ownership',
            'Kahramaa',
            'Parking',
            'Furniture',
            'Status',
            'Availability Status',
            'Flag',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->number,
            $row->type?->name,
            $row->building?->group?->name,
            $row->building?->name,
            $row->floor,
            $row->rent,
            $row->ownership,
            $row->kahramaa,
            $row->parking,
            $row->furniture,
            $row->status?->label(),
            ucfirst($row->availability_status ?? ''),
            ucfirst($row->flag ?? ''),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
