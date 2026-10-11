<?php

namespace App\Exports;

use App\Actions\PropertyAppointment\GetAction;
use App\Models\PropertyAppointment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropertyAppointmentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query(): Builder
    {
        return (new GetAction())->execute($this->filters)['list'];
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Date',
            'Time',
            'Customer',
            'Mobile',
            'Property',
            'Building',
            'Agreement',
            'Employee',
            'Status',
            'Booked By',
            'Booked At',
            'Link Sent At',
        ];
    }

    /**
     * @param  PropertyAppointment  $row
     */
    public function map($row): array
    {
        $endsAt = $row->endsAt();

        return [
            $row->reference_no,
            $row->scheduled_at?->format('Y-m-d'),
            $row->scheduled_at ? trim(appointmentTime($row->scheduled_at).($endsAt ? ' - '.appointmentTime($endsAt) : '')) : 'Not booked yet',
            $row->customer?->name,
            $row->customer?->mobile,
            $row->rentOut?->property?->number,
            $row->rentOut?->property?->building?->name,
            $row->rent_out_id ? '#'.$row->rent_out_id : null,
            $row->employee?->name,
            $row->statusLabel(),
            $row->booked_by ? ucfirst($row->booked_by) : null,
            $row->booked_at?->format('Y-m-d H:i'),
            $row->link_sent_at?->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
