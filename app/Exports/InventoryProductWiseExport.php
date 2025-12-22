<?php

namespace App\Exports;

use App\Actions\Product\InventoryProductWiseAction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryProductWiseExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return (new InventoryProductWiseAction())->execute($this->filters);
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Product Code',
            'Product Name (Arabic)',
            'Department',
            'Main Category',
            'Sub Category',
            'Brand',
            'Unit',
            'Size',
            'Total Quantity',
            'Average Cost',
            'Total Value',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->code,
            $row->name_arabic ?? '',
            $row->department_name ?? 'N/A',
            $row->main_category_name ?? 'N/A',
            $row->sub_category_name ?? 'N/A',
            $row->brand_name ?? 'N/A',
            $row->unit_name ?? 'N/A',
            $row->size ?? '',
            number_format($row->total_quantity ?? 0, 2),
            number_format($row->average_cost ?? 0, 2),
            number_format($row->total_value ?? 0, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
