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

class MonthlySaleReportExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
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
            'Month',
            'Gross Sales',
            'Discount',
            'Net Sale',
            'Paid (Total)',
            'Credit',
            'Card',
            'Cash',
        ];
    }

    public function map($row): array
    {
        return [
            $row['month_name'],
            round($row['gross_sales'], 2),
            round($row['discount'], 2),
            round($row['net_sale'], 2),
            round($row['paid_total'], 2),
            round($row['credit'], 2),
            round($row['card'], 2),
            round($row['cash'], 2),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_00,
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
                $sheet->setCellValue("B{$totalRows}", round($this->total['gross_sales'] ?? 0, 2));
                $sheet->setCellValue("C{$totalRows}", round($this->total['discount'] ?? 0, 2));
                $sheet->setCellValue("D{$totalRows}", round($this->total['net_sale'] ?? 0, 2));
                $sheet->setCellValue("E{$totalRows}", round($this->total['paid_total'] ?? 0, 2));
                $sheet->setCellValue("F{$totalRows}", round($this->total['credit'] ?? 0, 2));
                $sheet->setCellValue("G{$totalRows}", round($this->total['card'] ?? 0, 2));
                $sheet->setCellValue("H{$totalRows}", round($this->total['cash'] ?? 0, 2));

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
                if (! empty($this->filters)) {
                    $sheet->insertNewRowBefore(1, 2);
                    $sheet->mergeCells('A1:H1');
                    $title = 'MONTHLY SALE REPORT';
                    if (isset($this->filters['from_year']) && isset($this->filters['from_month']) &&
                        isset($this->filters['to_year']) && isset($this->filters['to_month'])) {
                        $fromMonth = date('M Y', mktime(0, 0, 0, (int) $this->filters['from_month'], 1, (int) $this->filters['from_year']));
                        $toMonth = date('M Y', mktime(0, 0, 0, (int) $this->filters['to_month'], 1, (int) $this->filters['to_year']));
                        $title .= ' - '.$fromMonth.' to '.$toMonth;
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
