<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TaxReportExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        public $entries,
        public array $filters = []
    ) {}

    public function collection()
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Transaction Type',
            'Reference',
            'Description',
            'Remarks',
            'Debit (Tax Credit)',
            'Credit (Tax Liability)',
        ];
    }

    public function map($entry): array
    {
        $transactionType = match($entry->model) {
            'Purchase' => 'Purchase',
            'PurchaseReturn' => 'Purchase Return',
            'Sale' => 'Sale',
            'SaleReturn' => 'Sale Return',
            default => $entry->model ?? '-'
        };

        return [
            systemDate($entry->date),
            $transactionType,
            $entry->reference_number ?? $entry->journal?->reference_number ?? '-',
            $entry->description ?? $entry->journal?->description ?? '-',
            $entry->remarks ?? '-',
            $entry->debit > 0 ? $entry->debit : 0,
            $entry->credit > 0 ? $entry->credit : 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Style headers
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Calculate totals
                $totalRows = $sheet->getHighestRow();
                $totalDebit = 0;
                $totalCredit = 0;

                for ($row = 2; $row <= $totalRows; $row++) {
                    $totalDebit += $sheet->getCell("F{$row}")->getValue() ?? 0;
                    $totalCredit += $sheet->getCell("G{$row}")->getValue() ?? 0;
                }

                // Add totals row
                $totalRow = $totalRows + 1;
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->setCellValue("B{$totalRow}", '');
                $sheet->setCellValue("C{$totalRow}", '');
                $sheet->setCellValue("D{$totalRow}", '');
                $sheet->setCellValue("E{$totalRow}", count($this->entries) . ' entries');
                $sheet->setCellValue("F{$totalRow}", $totalDebit);
                $sheet->setCellValue("G{$totalRow}", $totalCredit);

                // Style totals row
                $sheet->getStyle("A{$totalRow}:G{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'E6F3FF'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Add net payable row
                $netRow = $totalRow + 1;
                $netPayable = $totalCredit - $totalDebit;
                $sheet->setCellValue("A{$netRow}", 'NET TAX PAYABLE (Liability - Credit)');
                $sheet->mergeCells("A{$netRow}:E{$netRow}");
                $sheet->setCellValue("F{$netRow}", '');
                $sheet->setCellValue("G{$netRow}", $netPayable);

                // Style net payable row
                $sheet->getStyle("A{$netRow}:G{$netRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Add borders to all data rows
                if ($totalRows >= 2) {
                    $sheet->getStyle("A2:G{$totalRows}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => 'thin'],
                        ],
                    ]);
                }

                // Auto-fit columns
                foreach (range('A', 'G') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Add title if filters are provided
                if (!empty($this->filters)) {
                    $sheet->insertNewRowBefore(1, 2);
                    $sheet->mergeCells('A1:G1');
                    $title = 'TAX REPORT';
                    if (isset($this->filters['from_date']) && isset($this->filters['to_date'])) {
                        $title .= ' - '.systemDate($this->filters['from_date']).' to '.systemDate($this->filters['to_date']);
                    }
                    if (isset($this->filters['transaction_type']) && $this->filters['transaction_type'] !== 'all') {
                        $title .= ' ('.ucfirst($this->filters['transaction_type']).' Only)';
                    }
                    $sheet->setCellValue('A1', $title);
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                }
            },
        ];
    }
}

