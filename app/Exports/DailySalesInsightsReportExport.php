<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Excel copy of /report/daily_sales_insights: a title row, a two-row header
 * (fixed columns + Invoice/Due/Total under each payment method), the period ×
 * branch rows as shown on screen, and a totals row.
 *
 * @see \App\Livewire\Report\Sale\DailySalesInsightsReport
 */
class DailySalesInsightsReportExport implements FromArray, WithEvents, WithTitle
{
    use Exportable;

    private const FIXED_COLUMNS = ['Branch', 'Net Sales', 'Invoices', 'Discount', 'Total', 'Credit'];

    private const HEADER_START_ROW = 3;

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<string, float|int>  $total
     * @param  array<int, array{name: string, invoice_key: string, due_key: string, total_key: string}>  $paymentMethodColumns
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $data = [],
        public array $total = [],
        public array $paymentMethodColumns = [],
        public array $filters = [],
    ) {}

    public function title(): string
    {
        return 'Sales Insights';
    }

    public function array(): array
    {
        $rows = [
            [$this->reportTitle()],
            [$this->filterSummary()],
        ];

        $groupRow = array_merge([$this->periodHeading()], self::FIXED_COLUMNS);
        $subRow = array_fill(0, count($groupRow), '');
        foreach ($this->paymentMethodColumns as $method) {
            array_push($groupRow, $method['name'], '', '');
            array_push($subRow, 'Invoice', 'Due', 'Total');
        }
        $rows[] = $groupRow;
        $rows[] = $subRow;

        foreach ($this->data as $item) {
            $rows[] = array_merge([
                $this->isDaily() ? systemDate($item['date']) : $item['period_label'],
                $item['branch_name'],
            ], $this->amountCells($item));
        }

        $rows[] = array_merge(['TOTAL', ''], $this->amountCells($this->total));

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex($this->columnCount());
                $headerRow = self::HEADER_START_ROW;
                $subHeaderRow = $headerRow + 1;
                $firstDataRow = $subHeaderRow + 1;
                $totalRow = $sheet->getHighestRow();

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center'],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $fixedColumnCount = 1 + count(self::FIXED_COLUMNS);
                for ($index = 1; $index <= $fixedColumnCount; $index++) {
                    $column = Coordinate::stringFromColumnIndex($index);
                    $sheet->mergeCells("{$column}{$headerRow}:{$column}{$subHeaderRow}");
                }

                foreach (array_keys($this->paymentMethodColumns) as $position) {
                    $start = Coordinate::stringFromColumnIndex($fixedColumnCount + 1 + ($position * 3));
                    $end = Coordinate::stringFromColumnIndex($fixedColumnCount + 3 + ($position * 3));
                    $sheet->mergeCells("{$start}{$headerRow}:{$end}{$headerRow}");
                }

                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$subHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                $sheet->getStyle("A{$firstDataRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E6F3FF']],
                ]);

                $sheet->getStyle("C{$firstDataRow}:{$lastColumn}{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle("D{$firstDataRow}:D{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER);

                for ($index = 1; $index <= $this->columnCount(); $index++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
                }

                $sheet->freezePane('C'.$firstDataRow);
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, float|int>
     */
    private function amountCells(array $row): array
    {
        $cells = [
            (float) ($row['net_sales'] ?? 0),
            (int) ($row['no_of_invoices'] ?? 0),
            (float) ($row['sales_discount'] ?? 0),
            (float) ($row['total_sales'] ?? 0),
            (float) ($row['credit'] ?? 0),
        ];

        foreach ($this->paymentMethodColumns as $method) {
            $cells[] = (float) ($row[$method['invoice_key']] ?? 0);
            $cells[] = (float) ($row[$method['due_key']] ?? 0);
            $cells[] = (float) ($row[$method['total_key']] ?? 0);
        }

        return $cells;
    }

    private function columnCount(): int
    {
        return 1 + count(self::FIXED_COLUMNS) + (count($this->paymentMethodColumns) * 3);
    }

    private function isDaily(): bool
    {
        return ($this->filters['chart_period'] ?? 'daily') === 'daily';
    }

    private function periodHeading(): string
    {
        return ['weekly' => 'Week', 'monthly' => 'Month'][$this->filters['chart_period'] ?? 'daily'] ?? 'Date';
    }

    private function reportTitle(): string
    {
        $title = 'SALES INSIGHTS ('.strtoupper($this->filters['chart_period'] ?? 'daily').')';

        if (! empty($this->filters['from_date']) && ! empty($this->filters['to_date'])) {
            $title .= ' - '.systemDate($this->filters['from_date']).' to '.systemDate($this->filters['to_date']);
        }

        return $title;
    }

    private function filterSummary(): string
    {
        return 'Payment Method: '.(($this->filters['payment_method_name'] ?? null) ?: 'All');
    }
}
