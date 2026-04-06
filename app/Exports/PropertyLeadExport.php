<?php

namespace App\Exports;

use App\Actions\Property\PropertyLead\GetAction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropertyLeadExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return (new GetAction())->execute($this->filters)['list']
            ->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            '#',
            'Name',
            'Mobile',
            'Email',
            'Company Name',
            'Company Contact No',
            'Source',
            'Type',
            'Project / Group',
            'Assigned To',
            'Assign Date',
            'Nationality',
            'Location',
            'Meeting Date',
            'Meeting Time',
            'Status',
            'Notes',
            'Created At',
            'Updated At',
        ];
    }

    public function map($row): array
    {
        $notes = is_array($row->remarks) ? $row->remarks : (json_decode($row->remarks ?? '[]', true) ?: []);
        $notesText = collect($notes)
            ->map(fn ($n) => ($n['date'] ?? '').' - '.($n['note'] ?? ''))
            ->implode("\n");

        return [
            $row->id,
            $row->name,
            $row->mobile,
            $row->email,
            $row->company_name,
            $row->company_contact_no,
            $row->source,
            $row->type,
            $row->group?->name,
            $row->assignee?->name,
            $row->assign_date?->format('Y-m-d'),
            $row->country?->name ?? $row->nationality,
            $row->location,
            $row->meeting_date?->format('Y-m-d'),
            $row->meeting_time,
            $row->status,
            $notesText,
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
