<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductivityReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        public array $filters = [],
        public array $employees = [],
        public array $topCategories = [],
        public array $summaryData = []
    ) {}

    public function sheets(): array
    {
        return [
            '01_Executive_Summary' => new ProductivitySummarySheet($this->summaryData),
            '02_Employee_Performance' => new EmployeePerformanceSheet($this->employees),
            '03_Top_Categories_Analysis' => new TopCategoriesSheet($this->topCategories, $this->employees),
        ];
    }
}

class ProductivitySummarySheet implements FromCollection, WithEvents, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(public array $summaryData) {}

    public function title(): string
    {
        return 'Executive Summary';
    }

    public function collection()
    {
        return collect([
            [
                'Metric' => 'Total Sales',
                'Value' => $this->summaryData['totalSales'] ?? 0,
                'Currency' => 'QAR',
            ],
            [
                'Metric' => 'Total Transactions',
                'Value' => $this->summaryData['totalTransactions'] ?? 0,
                'Currency' => 'Count',
            ],
            [
                'Metric' => 'Total Items Sold',
                'Value' => $this->summaryData['totalItems'] ?? 0,
                'Currency' => 'Units',
            ],
            [
                'Metric' => 'Average Transaction Value',
                'Value' => $this->summaryData['avgTransaction'] ?? 0,
                'Currency' => 'QAR',
            ],
            [
                'Metric' => 'Report Period',
                'Value' => ($this->summaryData['fromDate'] ?? '').' to '.($this->summaryData['toDate'] ?? ''),
                'Currency' => 'Date Range',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Performance Metric',
            'Value',
            'Unit',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Add title
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:C1');
                $sheet->setCellValue('A1', 'EMPLOYEE PRODUCTIVITY REPORT - EXECUTIVE SUMMARY');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                // Style the data
                $sheet->getStyle('A3:C7')->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Format currency columns
                $sheet->getStyle('B4')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle('B6')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            },
        ];
    }
}

class EmployeePerformanceSheet implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(public array $employees) {}

    public function title(): string
    {
        return 'Employee Performance';
    }

    public function collection()
    {
        return collect($this->employees);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Email',
            'Total Transactions',
            'Total Sales (QAR)',
            'Items Sold',
            'Average Transaction Value (QAR)',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee['id'],
            $employee['name'],
            $employee['email'] ?? '',
            $employee['total_transactions'] ?? 0,
            $employee['total_sales'] ?? 0,
            $employee['items_sold'] ?? 0,
            $employee['avg_transaction_value'] ?? 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Add title
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'EMPLOYEE PRODUCTIVITY REPORT - PERFORMANCE MATRIX');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                // Add totals row
                $totalRows = $sheet->getHighestRow() + 1;
                $endRow = $totalRows - 1;

                $sheet->setCellValue("A{$totalRows}", 'TOTALS');
                $sheet->setCellValue("D{$totalRows}", "=SUM(D3:D{$endRow})");
                $sheet->setCellValue("E{$totalRows}", "=SUM(E3:E{$endRow})");
                $sheet->setCellValue("F{$totalRows}", "=SUM(F3:F{$endRow})");
                $sheet->setCellValue("G{$totalRows}", "=AVERAGE(G3:G{$endRow})");

                // Style totals row
                $sheet->getStyle("A{$totalRows}:G{$totalRows}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'E6F3FF'],
                    ],
                ]);

                // Style headers
                $sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);

                // Add borders
                $sheet->getStyle("A2:G{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Auto-fit columns
                foreach (range('A', 'G') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}

class TopCategoriesSheet implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(public array $topCategories, public array $employees = []) {}

    public function title(): string
    {
        return 'Top Categories Analysis';
    }

    public function collection()
    {
        $data = [];
        foreach ($this->topCategories as $employeeId => $categories) {
            foreach ($categories as $category) {
                $data[] = [
                    'employee_id' => $category['employee_id'],
                    'employee_name' => $category['employee_name'],
                    'category' => $category['category'],
                    'items_sold' => $category['count'],
                    'total_sales' => $category['total'],
                ];
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Category',
            'Items Sold',
            'Total Sales (QAR)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee_id'],
            $row['employee_name'],
            $row['category'],
            $row['items_sold'],
            $row['total_sales'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Add title
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'EMPLOYEE PRODUCTIVITY REPORT - TOP CATEGORIES ANALYSIS');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                // Style headers
                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);

                // Add borders
                $totalRows = $sheet->getHighestRow();
                $sheet->getStyle("A2:E{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => 'thin'],
                    ],
                ]);

                // Auto-fit columns
                foreach (range('A', 'E') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
