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

/**
 * The Category Wise sale report as a sheet: one row per main category, a
 * totals row, and a title line with the period.
 *
 * @see \App\Livewire\Report\Sale\CategoryWiseReport
 */
class CategoryWiseSaleReportExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
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
            'Category',
            'Products',
            'Bills',
            'Qty Sold',
            'Qty Returned',
            'Net Qty',
            'Gross Amount',
            'Discount',
            'Tax Amount',
            'Sales',
            'Returns',
            'Net Sales',
            'Share %',
        ];
    }

    public function map($row): array
    {
        $netTotal = (float) ($this->total['net_total'] ?? 0);

        return [
            $row->group_name ?? 'Uncategorised',
            (int) $row->products_count,
            (int) $row->bills_count,
            (float) $row->sale_qty,
            (float) $row->return_qty,
            (float) $row->quantity,
            (float) $row->gross_amount,
            (float) $row->discount,
            (float) $row->tax_amount,
            (float) $row->sale_total,
            (float) $row->return_total,
            (float) $row->net_total,
            $netTotal > 0 ? round(((float) $row->net_total / $netTotal) * 100, 2) : 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                $totalRow = $sheet->getHighestRow() + 1;
                $endRow = $totalRow - 1;

                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->setCellValue("B{$totalRow}", $this->total['products_count'] ?? 0);
                $sheet->setCellValue("C{$totalRow}", $this->total['bills_count'] ?? 0);
                $sheet->setCellValue("D{$totalRow}", $this->total['sale_qty'] ?? 0);
                $sheet->setCellValue("E{$totalRow}", $this->total['return_qty'] ?? 0);
                $sheet->setCellValue("F{$totalRow}", $this->total['quantity'] ?? 0);
                $sheet->setCellValue("G{$totalRow}", $this->total['gross_amount'] ?? 0);
                $sheet->setCellValue("H{$totalRow}", $this->total['discount'] ?? 0);
                $sheet->setCellValue("I{$totalRow}", $this->total['tax_amount'] ?? 0);
                $sheet->setCellValue("J{$totalRow}", $this->total['sale_total'] ?? 0);
                $sheet->setCellValue("K{$totalRow}", $this->total['return_total'] ?? 0);
                $sheet->setCellValue("L{$totalRow}", $this->total['net_total'] ?? 0);
                $sheet->setCellValue("M{$totalRow}", ($this->total['net_total'] ?? 0) > 0 ? 100 : 0);

                $sheet->getStyle("A{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E6F3FF']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                if ($endRow >= 2) {
                    $sheet->getStyle("A2:M{$endRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                    ]);
                }

                foreach (range('A', 'M') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                if (! empty($this->filters['from_date']) && ! empty($this->filters['to_date'])) {
                    $sheet->insertNewRowBefore(1, 2);
                    $sheet->mergeCells('A1:M1');
                    $sheet->setCellValue('A1', 'CATEGORY WISE SALE REPORT - '.systemDate($this->filters['from_date']).' to '.systemDate($this->filters['to_date']));
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                }
            },
        ];
    }
}
