<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialBalanceExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $data;

    protected string $startDate;

    protected string $endDate;

    protected ?string $branchName;

    private const HEADER_ROW_COUNT = 4;

    private const HEADER_ROW = 1;

    private const LAST_COLUMN = 'C';

    // Color constants - Professional corporate palette
    private const HEADER_BG = '2C3E50'; // Professional dark blue-gray

    private const HEADER_TEXT = 'FFFFFF'; // White

    private const SECTION_BG = 'ECF0F1'; // Soft light gray

    private const TOTAL_BG = '34495E'; // Professional dark gray-blue

    private const TOTAL_TEXT = 'FFFFFF'; // White

    private const GRAY_FILL = 'F8F9FA'; // Very light gray

    private const COLUMN_HEADER_BG = '34495E'; // Professional dark gray-blue

    private const COLUMN_HEADER_TEXT = 'FFFFFF'; // White

    // Section colors
    private const ASSETS_COLOR = '3498DB'; // Blue
    private const LIABILITIES_COLOR = 'F39C12'; // Orange
    private const EQUITY_COLOR = '27AE60'; // Green
    private const INCOME_COLOR = '27AE60'; // Green
    private const EXPENSES_COLOR = 'E74C3C'; // Red

    public function __construct(array $data, string $startDate, string $endDate, ?string $branchName = null)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->branchName = $branchName;
    }

    public function collection(): Collection
    {
        $rows = [];

        // Helper function to flatten tree structure
        $flattenTree = function ($tree, $sectionName, $indent = 0) use (&$rows, &$flattenTree) {
            foreach ($tree as $key => $item) {
                if ($key === 'uncategorized') {
                    // Handle uncategorized accounts
                    foreach ($item as $account) {
                        $rows[] = [
                            'account_name' => str_repeat('  ', $indent).$account['name'],
                            'debit' => $account['debit'] ?? 0,
                            'credit' => $account['credit'] ?? 0,
                        ];
                    }
                } elseif (isset($item['name'])) {
                    // This is a category or group
                    $isCategory = isset($item['groups']) || isset($item['directAccounts']);

                    if ($isCategory) {
                        // Category header
                        $rows[] = [
                            'account_name' => str_repeat('  ', $indent).$item['name'],
                            'debit' => $item['debit'] ?? 0,
                            'credit' => $item['credit'] ?? 0,
                        ];

                        // Direct accounts under category
                        if (isset($item['directAccounts'])) {
                            foreach ($item['directAccounts'] as $account) {
                                $rows[] = [
                                    'account_name' => str_repeat('  ', $indent + 1).$account['name'],
                                    'debit' => $account['debit'] ?? 0,
                                    'credit' => $account['credit'] ?? 0,
                                ];
                            }
                        }

                        // Groups under category
                        if (isset($item['groups'])) {
                            foreach ($item['groups'] as $group) {
                                $rows[] = [
                                    'account_name' => str_repeat('  ', $indent + 1).$group['name'],
                                    'debit' => $group['debit'] ?? 0,
                                    'credit' => $group['credit'] ?? 0,
                                ];

                                // Accounts under group
                                if (isset($group['accounts'])) {
                                    foreach ($group['accounts'] as $account) {
                                        $rows[] = [
                                            'account_name' => str_repeat('  ', $indent + 2).$account['name'],
                                            'debit' => $account['debit'] ?? 0,
                                            'credit' => $account['credit'] ?? 0,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        };

        // Assets Section
        if (!empty($this->data['assetsTree'])) {
            $rows[] = ['account_name' => 'ASSETS', 'debit' => '', 'credit' => ''];
            $flattenTree($this->data['assetsTree'], 'assets');
            $rows[] = [
                'account_name' => 'Total Assets',
                'debit' => $this->data['totalAssetsDebit'] ?? 0,
                'credit' => $this->data['totalAssetsCredit'] ?? 0,
            ];
            $rows[] = ['account_name' => '', 'debit' => '', 'credit' => '']; // Empty row
        }

        // Liabilities Section
        if (!empty($this->data['liabilitiesTree'])) {
            $rows[] = ['account_name' => 'LIABILITIES', 'debit' => '', 'credit' => ''];
            $flattenTree($this->data['liabilitiesTree'], 'liabilities');
            $rows[] = [
                'account_name' => 'Total Liabilities',
                'debit' => $this->data['totalLiabilitiesDebit'] ?? 0,
                'credit' => $this->data['totalLiabilitiesCredit'] ?? 0,
            ];
            $rows[] = ['account_name' => '', 'debit' => '', 'credit' => '']; // Empty row
        }

        // Equity Section
        if (!empty($this->data['equityTree'])) {
            $rows[] = ['account_name' => 'EQUITY', 'debit' => '', 'credit' => ''];
            $flattenTree($this->data['equityTree'], 'equity');
            $rows[] = [
                'account_name' => 'Total Equity',
                'debit' => $this->data['totalEquityDebit'] ?? 0,
                'credit' => $this->data['totalEquityCredit'] ?? 0,
            ];
            $rows[] = ['account_name' => '', 'debit' => '', 'credit' => '']; // Empty row
        }

        // Income Section
        if (!empty($this->data['incomeTree'])) {
            $rows[] = ['account_name' => 'INCOME', 'debit' => '', 'credit' => ''];
            $flattenTree($this->data['incomeTree'], 'income');
            $rows[] = [
                'account_name' => 'Total Income',
                'debit' => $this->data['totalIncomeDebit'] ?? 0,
                'credit' => $this->data['totalIncomeCredit'] ?? 0,
            ];
            $rows[] = ['account_name' => '', 'debit' => '', 'credit' => '']; // Empty row
        }

        // Expenses Section
        if (!empty($this->data['expensesTree'])) {
            $rows[] = ['account_name' => 'EXPENSES', 'debit' => '', 'credit' => ''];
            $flattenTree($this->data['expensesTree'], 'expenses');
            $rows[] = [
                'account_name' => 'Total Expenses',
                'debit' => $this->data['totalExpensesDebit'] ?? 0,
                'credit' => $this->data['totalExpensesCredit'] ?? 0,
            ];
            $rows[] = ['account_name' => '', 'debit' => '', 'credit' => '']; // Empty row
        }

        // Grand Total
        $rows[] = [
            'account_name' => 'GRAND TOTAL',
            'debit' => $this->data['totalDebit'] ?? 0,
            'credit' => $this->data['totalCredit'] ?? 0,
        ];

        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['Account Name', 'Debit', 'Credit'];
    }

    public function map($row): array
    {
        return [
            $row['account_name'] ?? '',
            is_numeric($row['debit']) ? $row['debit'] : '',
            is_numeric($row['credit']) ? $row['credit'] : '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_00,
            'C' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = self::HEADER_ROW_COUNT + 1;

        return [
            $headerRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => self::COLUMN_HEADER_TEXT]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $this->addHeaderRows($sheet);
                $this->styleColumnHeaders($sheet);
                $this->styleSectionHeaders($sheet);
                $this->styleTotalRows($sheet);
                $this->addBorders($sheet);
                $this->autoSizeColumns($sheet);
            },
        ];
    }

    protected function addHeaderRows(Worksheet $sheet): void
    {
        $sheet->insertNewRowBefore(self::HEADER_ROW, self::HEADER_ROW_COUNT);

        $sheet->setCellValue('A1', 'Trial Balance Report');
        $sheet->setCellValue('A2', 'Period: '.systemDate($this->startDate).' to '.systemDate($this->endDate));
        if ($this->branchName) {
            $sheet->setCellValue('A3', 'Branch: '.$this->branchName);
        } else {
            $sheet->setCellValue('A3', 'Branch: All Branches');
        }
        $sheet->setCellValue('A4', 'Generated: '.now()->format('d-m-Y H:i:s'));

        // Main title styling
        $sheet->getStyle('A1:'.self::LAST_COLUMN.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::HEADER_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::HEADER_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Info rows styling
        $sheet->getStyle('A2:'.self::LAST_COLUMN.'4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::GRAY_FILL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A1:'.self::LAST_COLUMN.'1');
        $sheet->mergeCells('A2:'.self::LAST_COLUMN.'2');
        $sheet->mergeCells('A3:'.self::LAST_COLUMN.'3');
        $sheet->mergeCells('A4:'.self::LAST_COLUMN.'4');

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);
    }

    protected function styleSectionHeaders(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $dataStartRow = self::HEADER_ROW_COUNT + 2;

        $sectionColors = [
            'ASSETS' => self::ASSETS_COLOR,
            'LIABILITIES' => self::LIABILITIES_COLOR,
            'EQUITY' => self::EQUITY_COLOR,
            'INCOME' => self::INCOME_COLOR,
            'EXPENSES' => self::EXPENSES_COLOR,
        ];

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $accountName = $sheet->getCell("A{$row}")->getValue();

            // Style section headers (ASSETS, LIABILITIES, etc.)
            if (isset($sectionColors[$accountName])) {
                $sheet->mergeCells("A{$row}:".self::LAST_COLUMN."{$row}");
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $sectionColors[$accountName]],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(25);
            }
        }
    }

    protected function styleTotalRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $dataStartRow = self::HEADER_ROW_COUNT + 2;

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $accountName = $sheet->getCell("A{$row}")->getValue();

            // Style total rows (Total Assets, Total Liabilities, etc. and GRAND TOTAL)
            if (str_starts_with($accountName, 'Total ') || $accountName === 'GRAND TOTAL') {
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::TOTAL_TEXT]],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::TOTAL_BG],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(22);
            }
        }
    }

    protected function styleColumnHeaders(Worksheet $sheet): void
    {
        $headerRow = self::HEADER_ROW_COUNT + 1;
        $sheet->getStyle("A{$headerRow}:".self::LAST_COLUMN."{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::COLUMN_HEADER_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLUMN_HEADER_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(25);
    }

    protected function addBorders(Worksheet $sheet): void
    {
        $dataStartRow = self::HEADER_ROW_COUNT + 2;
        $highestRow = $sheet->getHighestRow();

        // Add borders to all data rows
        $sheet->getStyle("A{$dataStartRow}:".self::LAST_COLUMN."{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
    }

    protected function autoSizeColumns(Worksheet $sheet): void
    {
        foreach (range('A', self::LAST_COLUMN) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set minimum column widths
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
    }
}

