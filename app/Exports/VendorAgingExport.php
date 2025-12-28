<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class VendorAgingExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        public $data = [],
        public array $totals = [],
        public array $filters = []
    ) {}

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Vendor Name',
            'Mobile',
            'Credit Period (Days)',
            'Invoice No',
            'Invoice Date',
            'Due Date',
            'Days Overdue',
            'Invoice Amount',
            'Amount Paid',
            'Outstanding Balance',
            '0-30 Days',
            '31-60 Days',
            '61-90 Days',
            '90+ Days',
        ];
    }

    public function map($row): array
    {
        return [
            $row->vendor_name ?? '',
            $row->vendor_mobile ?? '',
            $row->credit_period_days ? $row->credit_period_days.' days' : 'N/A',
            $row->invoice_no ?? '',
            $row->invoice_date ? systemDate($row->invoice_date) : '',
            $row->due_date ? systemDate($row->due_date) : '',
            $row->days_overdue ?? 0,
            $row->invoice_amount ?? 0,
            $row->amount_paid ?? 0,
            $row->outstanding_balance ?? 0,
            $row->aging_0_30 ?? 0,
            $row->aging_31_60 ?? 0,
            $row->aging_61_90 ?? 0,
            $row->aging_90_plus ?? 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Style headers
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                    ],
                ];
                $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

                // Add title if filters are provided
                if (! empty($this->filters)) {
                    $sheet->insertNewRowBefore(1, 2);
                    $title = 'VENDOR AGING REPORT';
                    if (isset($this->filters['from_date']) && isset($this->filters['to_date'])) {
                        $title .= ' - '.systemDate($this->filters['from_date']).' to '.systemDate($this->filters['to_date']);
                    }
                    $sheet->mergeCells('A1:N1');
                    $sheet->setCellValue('A1', $title);
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $lastRow += 2;
                }

                // Add totals row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
                $sheet->setCellValue("H{$totalRow}", $this->totals['totalInvoiceAmount'] ?? 0);
                $sheet->setCellValue("I{$totalRow}", $this->totals['totalAmountPaid'] ?? 0);
                $sheet->setCellValue("J{$totalRow}", $this->totals['totalOutstanding'] ?? 0);
                $sheet->setCellValue("K{$totalRow}", $this->totals['total0to30'] ?? 0);
                $sheet->setCellValue("L{$totalRow}", $this->totals['total31to60'] ?? 0);
                $sheet->setCellValue("M{$totalRow}", $this->totals['total61to90'] ?? 0);
                $sheet->setCellValue("N{$totalRow}", $this->totals['total90Plus'] ?? 0);

                // Style totals row
                $totalStyle = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E6F3FF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle("A{$totalRow}:N{$totalRow}")->applyFromArray($totalStyle);

                // Add borders to all data rows
                $dataStartRow = ! empty($this->filters) ? 3 : 2;
                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle("A{$dataStartRow}:N{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }

                // Auto-fit columns
                foreach (range('A', 'N') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Set alignment for numeric columns
                $sheet->getStyle("H2:N{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("H{$totalRow}:N{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
