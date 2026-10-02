<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketImportTemplate implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            ['Receipt not printing after sale', 'Printer stays idle when charging a cash sale.', 'Open', 'Sales', date('Y-m-d')],
            ['Stock count doubled on rescan', 'Scanning the same barcode twice doubles the counted qty.', 'In Progress', 'Inventory', date('Y-m-d')],
            ['VAT rounding on credit notes', 'Credit note total is off by 0.01.', 'Resolved', 'Accounts', ''],
        ];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Title', 'Description', 'Status', 'Group', 'Created Date'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
            ],
        ];
    }
}
