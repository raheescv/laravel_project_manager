<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ChecklistItemImportTemplate implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'name' => 'Ceiling',
                'category' => 'Living Room',
                'property_type' => 'Apartment',
                'sort_order' => '1',
                'is_active' => 'yes',
            ],
            [
                'name' => 'Light Switches',
                'category' => 'Living Room',
                'property_type' => 'Apartment',
                'sort_order' => '2',
                'is_active' => 'yes',
            ],
            [
                'name' => 'Water Heater',
                'category' => 'Bathroom',
                'property_type' => '',
                'sort_order' => '3',
                'is_active' => 'yes',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'category',
            'property_type',
            'sort_order',
            'is_active',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D6EFD'],
                ],
            ],
        ];
    }
}
