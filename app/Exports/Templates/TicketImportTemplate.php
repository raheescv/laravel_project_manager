<?php

namespace App\Exports\Templates;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketImportTemplate implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * Sample rows written with the tenant's own column names, so the file imports as-is.
     *
     * @return list<list<string>>
     */
    public function array(): array
    {
        $statuses = array_values(Ticket::statuses());
        $status = fn (int $i): string => $statuses[min($i, count($statuses) - 1)] ?? '';

        return [
            ['Receipt not printing after sale', 'Printer stays idle when charging a cash sale.', $status(0), 'Urgent', 'Sales', date('Y-m-d')],
            ['Stock count doubled on rescan', 'Scanning the same barcode twice doubles the counted qty.', $status(1), 'High', 'Inventory', date('Y-m-d')],
            ['VAT rounding on credit notes', 'Credit note total is off by 0.01.', $status(2), '', 'Accounts', ''],
        ];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Title', 'Description', 'Status', 'Priority', 'Group', 'Created Date'];
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
