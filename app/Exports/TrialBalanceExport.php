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

    private const LAST_COLUMN = 'E';

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
                        $debit = $account['debit'] ?? 0;
                        $credit = $account['credit'] ?? 0;
                        $balance = is_numeric($debit) && is_numeric($credit) ? round($debit - $credit, 2) : '';
                        $rows[] = [
                            'account_name' => $account['name'],
                            'group' => '',
                            'debit' => $debit,
                            'credit' => $credit,
                            'balance' => $balance,
                        ];
                    }
                } elseif (isset($item['name'])) {
                    // This is a category or group
                    $isCategory = isset($item['groups']) || isset($item['directAccounts']);

                    if ($isCategory) {
                        $categoryName = $item['name'];
                        $categoryDebit = $item['debit'] ?? 0;
                        $categoryCredit = $item['credit'] ?? 0;
                        $categoryBalance = is_numeric($categoryDebit) && is_numeric($categoryCredit) ? round($categoryDebit - $categoryCredit, 2) : '';

                        // Category header
                        $rows[] = [
                            'account_name' => $categoryName,
                            'group' => '',
                            'debit' => $categoryDebit,
                            'credit' => $categoryCredit,
                            'balance' => $categoryBalance,
                        ];

                        // Direct accounts under category
                        if (isset($item['directAccounts'])) {
                            foreach ($item['directAccounts'] as $account) {
                                $debit = $account['debit'] ?? 0;
                                $credit = $account['credit'] ?? 0;
                                $balance = is_numeric($debit) && is_numeric($credit) ? round($debit - $credit, 2) : '';
                                $rows[] = [
                                    'account_name' => $account['name'],
                                    'group' => $categoryName,
                                    'debit' => $debit,
                                    'credit' => $credit,
                                    'balance' => $balance,
                                ];
                            }
                        }

                        // Groups under category
                        if (isset($item['groups'])) {
                            foreach ($item['groups'] as $group) {
                                $groupName = $group['name'];
                                $groupDebit = $group['debit'] ?? 0;
                                $groupCredit = $group['credit'] ?? 0;
                                $groupBalance = is_numeric($groupDebit) && is_numeric($groupCredit) ? round($groupDebit - $groupCredit, 2) : '';

                                // Group header
                                $rows[] = [
                                    'account_name' => $groupName,
                                    'group' => $categoryName,
                                    'debit' => $groupDebit,
                                    'credit' => $groupCredit,
                                    'balance' => $groupBalance,
                                ];

                                // Accounts under group
                                if (isset($group['accounts'])) {
                                    foreach ($group['accounts'] as $account) {
                                        $debit = $account['debit'] ?? 0;
                                        $credit = $account['credit'] ?? 0;
                                        $balance = is_numeric($debit) && is_numeric($credit) ? round($debit - $credit, 2) : '';
                                        $rows[] = [
                                            'account_name' => $account['name'],
                                            'group' => $groupName,
                                            'debit' => $debit,
                                            'credit' => $credit,
                                            'balance' => $balance,
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
        if (! empty($this->data['assetsTree'])) {
            $rows[] = ['account_name' => 'ASSETS', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['assetsTree'], 'assets');
            $totalAssetsDebit = $this->data['totalAssetsDebit'] ?? 0;
            $totalAssetsCredit = $this->data['totalAssetsCredit'] ?? 0;
            $totalAssetsBalance = is_numeric($totalAssetsDebit) && is_numeric($totalAssetsCredit) ? round($totalAssetsDebit - $totalAssetsCredit, 2) : '';
            $rows[] = [
                'account_name' => 'Total Assets',
                'group' => '',
                'debit' => $totalAssetsDebit,
                'credit' => $totalAssetsCredit,
                'balance' => $totalAssetsBalance,
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => '']; // Empty row
        }

        // Liabilities Section
        if (! empty($this->data['liabilitiesTree'])) {
            $rows[] = ['account_name' => 'LIABILITIES', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['liabilitiesTree'], 'liabilities');
            $totalLiabilitiesDebit = $this->data['totalLiabilitiesDebit'] ?? 0;
            $totalLiabilitiesCredit = $this->data['totalLiabilitiesCredit'] ?? 0;
            $totalLiabilitiesBalance = is_numeric($totalLiabilitiesDebit) && is_numeric($totalLiabilitiesCredit) ? round($totalLiabilitiesDebit - $totalLiabilitiesCredit, 2) : '';
            $rows[] = [
                'account_name' => 'Total Liabilities',
                'group' => '',
                'debit' => $totalLiabilitiesDebit,
                'credit' => $totalLiabilitiesCredit,
                'balance' => $totalLiabilitiesBalance,
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => '']; // Empty row
        }

        // Equity Section
        if (! empty($this->data['equityTree'])) {
            $rows[] = ['account_name' => 'EQUITY', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['equityTree'], 'equity');
            $totalEquityDebit = $this->data['totalEquityDebit'] ?? 0;
            $totalEquityCredit = $this->data['totalEquityCredit'] ?? 0;
            $totalEquityBalance = is_numeric($totalEquityDebit) && is_numeric($totalEquityCredit) ? round($totalEquityDebit - $totalEquityCredit, 2) : '';
            $rows[] = [
                'account_name' => 'Total Equity',
                'group' => '',
                'debit' => $totalEquityDebit,
                'credit' => $totalEquityCredit,
                'balance' => $totalEquityBalance,
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => '']; // Empty row
        }

        // Income Section
        if (! empty($this->data['incomeTree'])) {
            $rows[] = ['account_name' => 'INCOME', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['incomeTree'], 'income');
            $totalIncomeDebit = $this->data['totalIncomeDebit'] ?? 0;
            $totalIncomeCredit = $this->data['totalIncomeCredit'] ?? 0;
            $totalIncomeBalance = is_numeric($totalIncomeDebit) && is_numeric($totalIncomeCredit) ? round($totalIncomeDebit - $totalIncomeCredit, 2) : '';
            $rows[] = [
                'account_name' => 'Total Income',
                'group' => '',
                'debit' => $totalIncomeDebit,
                'credit' => $totalIncomeCredit,
                'balance' => $totalIncomeBalance,
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => '']; // Empty row
        }

        // Expenses Section
        if (! empty($this->data['expensesTree'])) {
            $rows[] = ['account_name' => 'EXPENSES', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['expensesTree'], 'expenses');
            $totalExpensesDebit = $this->data['totalExpensesDebit'] ?? 0;
            $totalExpensesCredit = $this->data['totalExpensesCredit'] ?? 0;
            $totalExpensesBalance = is_numeric($totalExpensesDebit) && is_numeric($totalExpensesCredit) ? round($totalExpensesDebit - $totalExpensesCredit, 2) : '';
            $rows[] = [
                'account_name' => 'Total Expenses',
                'group' => '',
                'debit' => $totalExpensesDebit,
                'credit' => $totalExpensesCredit,
                'balance' => $totalExpensesBalance,
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => '']; // Empty row
        }

        // Grand Total
        $totalDebit = $this->data['totalDebit'] ?? 0;
        $totalCredit = $this->data['totalCredit'] ?? 0;
        $grandTotalBalance = is_numeric($totalDebit) && is_numeric($totalCredit) ? round($totalDebit - $totalCredit, 2) : '';
        $rows[] = [
            'account_name' => 'GRAND TOTAL',
            'group' => '',
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'balance' => $grandTotalBalance,
        ];

        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['Group', 'Account Name', 'Debit', 'Credit', 'Balance'];
    }

    public function map($row): array
    {
        return [
            $row['group'] ?? '',
            $row['account_name'] ?? '',
            is_numeric($row['debit']) ? $row['debit'] : '',
            is_numeric($row['credit']) ? $row['credit'] : '',
            is_numeric($row['balance']) ? $row['balance'] : ($row['balance'] ?? ''),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
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
                $this->styleCategoryAndGroupRows($sheet);
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
            // Check account name column (B) for section headers
            $accountName = $sheet->getCell("B{$row}")->getValue();

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

    protected function styleCategoryAndGroupRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $dataStartRow = self::HEADER_ROW_COUNT + 2;

        $sectionHeaders = ['ASSETS', 'LIABILITIES', 'EQUITY', 'INCOME', 'EXPENSES'];
        $totalKeywords = ['Total Assets', 'Total Liabilities', 'Total Equity', 'Total Income', 'Total Expenses', 'GRAND TOTAL'];

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $group = trim($sheet->getCell("A{$row}")->getValue() ?? '');
            $accountName = trim($sheet->getCell("B{$row}")->getValue() ?? '');

            // Skip section headers and totals (handled separately)
            if (in_array($accountName, $sectionHeaders) || in_array($accountName, $totalKeywords)) {
                continue;
            }

            // Style category rows (rows where group is empty but account name is not empty and not a section header)
            if (empty($group) && ! empty($accountName)) {
                // This is a category header
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::SECTION_BG],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
            } elseif (! empty($group) && empty($accountName)) {
                // Row with group but no account name - likely a group header row
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F5F5F5'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
            } elseif (! empty($group) && ! empty($accountName)) {
                // Check if this is a group header by checking if the account name appears as a group in the next row
                $isGroupHeader = false;
                if ($row < $highestRow) {
                    $nextRowGroup = trim($sheet->getCell('A'.($row + 1))->getValue() ?? '');
                    // If next row's group matches this row's account name, this is a group header
                    if ($nextRowGroup === $accountName) {
                        $isGroupHeader = true;
                    }
                }

                if ($isGroupHeader) {
                    // This is a group header
                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '000000']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F5F5F5'],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    ]);
                } else {
                    // This is a regular account - use alternating row colors for better readability
                    $isEvenRow = ($row % 2 === 0);
                    $bgColor = $isEvenRow ? 'FAFAFA' : 'FFFFFF';

                    // Apply fill and alignment together to ensure all cells get the background
                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '000000']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                    ]);

                    // Apply alignment with fill to ensure background is preserved
                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                    ]);

                    $sheet->getStyle("C{$row}:E{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                    ]);
                }
            }
        }
    }

    protected function styleTotalRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $dataStartRow = self::HEADER_ROW_COUNT + 2;

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            // Check account name column (B) for total rows
            $accountName = $sheet->getCell("B{$row}")->getValue();

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

        // Style all headers with common properties
        $sheet->getStyle("A{$headerRow}:".self::LAST_COLUMN."{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::COLUMN_HEADER_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLUMN_HEADER_BG],
            ],
            'vertical' => Alignment::VERTICAL_CENTER,
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);

        // Left align Group and Account Name columns
        $sheet->getStyle("A{$headerRow}:B{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Center align Debit, Credit, and Balance columns
        $sheet->getStyle("C{$headerRow}:E{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
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
        // A = Group, B = Account Name, C = Debit, D = Credit, E = Balance
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
    }
}
