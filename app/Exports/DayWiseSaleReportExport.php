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

class DayWiseSaleReportExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        public array $data = [],
        public array $total = [],
        public array $filters = []
    ) {}

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Count',
            'Quantity',
            'Net Sale',
            'Gross Sale',
            'Tax Amount',
            'Discount',
            'Return Amount',
        ];
    }

    public function map($row): array
    {
        return [
            systemDate($row['date']),
            $row['count'],
            $row['quantity'],
            $row['net_sale'],
            $row['gross_sale'],
            $row['tax_amount'],
            $row['discount'],
            $row['return_amount'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Style headers
                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Add totals row
                $totalRows = $sheet->getHighestRow() + 1;
                $endRow = $totalRows - 1;

                $sheet->setCellValue("A{$totalRows}", 'TOTAL');
                $sheet->setCellValue("B{$totalRows}", $this->total['count'] ?? 0);
                $sheet->setCellValue("C{$totalRows}", $this->total['quantity'] ?? 0);
                $sheet->setCellValue("D{$totalRows}", $this->total['net_sale'] ?? 0);
                $sheet->setCellValue("E{$totalRows}", $this->total['gross_sale'] ?? 0);
                $sheet->setCellValue("F{$totalRows}", $this->total['tax_amount'] ?? 0);
                $sheet->setCellValue("G{$totalRows}", $this->total['discount'] ?? 0);
                $sheet->setCellValue("H{$totalRows}", $this->total['return_amount'] ?? 0);

                // Style totals row
                $sheet->getStyle("A{$totalRows}:H{$totalRows}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'E6F3FF'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Add borders to all data rows
                if ($endRow >= 2) {
                    $sheet->getStyle("A2:H{$endRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => 'thin'],
                        ],
                    ]);
                }

                // Auto-fit columns
                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Add title if filters are provided
                if (!empty($this->filters)) {
                    $sheet->insertNewRowBefore(1, 2);
                    $sheet->mergeCells('A1:H1');
                    $title = 'DAY WISE SALE REPORT';
                    if (isset($this->filters['from_date']) && isset($this->filters['to_date'])) {
                        $title .= ' - '.systemDate($this->filters['from_date']).' to '.systemDate($this->filters['to_date']);
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

