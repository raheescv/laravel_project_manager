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

class ProfitLossStatementExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $data;

    protected string $startDate;

    protected string $endDate;

    protected ?string $branchName;

    private const HEADER_ROW_COUNT = 4;

    private const HEADER_ROW = 1;

    private const LAST_COLUMN = 'E';

    // Color constants
    private const HEADER_BG = '2C3E50';

    private const HEADER_TEXT = 'FFFFFF';

    private const SECTION_BG = 'ECF0F1';

    private const TOTAL_BG = '34495E';

    private const TOTAL_TEXT = 'FFFFFF';

    private const GRAY_FILL = 'F8F9FA';

    private const COLUMN_HEADER_BG = '34495E';

    private const COLUMN_HEADER_TEXT = 'FFFFFF';

    // Section colors
    private const INCOME_COLOR = '27AE60';

    private const EXPENSES_COLOR = 'E74C3C';

    private const OTHER_COLOR = '95A5A6';

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

        $flattenTree = function ($tree) use (&$rows) {
            foreach ($tree as $key => $item) {
                if ($key === 'uncategorized') {
                    foreach ($item as $account) {
                        $debit = $account['debit'] ?? 0;
                        $credit = $account['credit'] ?? 0;
                        $rows[] = [
                            'account_name' => $account['name'],
                            'group' => '',
                            'debit' => $debit,
                            'credit' => $credit,
                            'balance' => round($debit - $credit, 2),
                        ];
                    }
                } elseif (isset($item['name'])) {
                    $isCategory = isset($item['groups']) || isset($item['directAccounts']);

                    if ($isCategory) {
                        $categoryName = $item['name'];
                        $rows[] = [
                            'account_name' => $categoryName,
                            'group' => '',
                            'debit' => $item['debit'] ?? 0,
                            'credit' => $item['credit'] ?? 0,
                            'balance' => round(($item['debit'] ?? 0) - ($item['credit'] ?? 0), 2),
                        ];

                        if (isset($item['directAccounts'])) {
                            foreach ($item['directAccounts'] as $account) {
                                $rows[] = [
                                    'account_name' => $account['name'],
                                    'group' => $categoryName,
                                    'debit' => $account['debit'] ?? 0,
                                    'credit' => $account['credit'] ?? 0,
                                    'balance' => round(($account['debit'] ?? 0) - ($account['credit'] ?? 0), 2),
                                ];
                            }
                        }

                        if (isset($item['groups'])) {
                            foreach ($item['groups'] as $group) {
                                $groupName = $group['name'];
                                $rows[] = [
                                    'account_name' => $groupName,
                                    'group' => $categoryName,
                                    'debit' => $group['debit'] ?? 0,
                                    'credit' => $group['credit'] ?? 0,
                                    'balance' => round(($group['debit'] ?? 0) - ($group['credit'] ?? 0), 2),
                                ];

                                if (isset($group['accounts'])) {
                                    foreach ($group['accounts'] as $account) {
                                        $rows[] = [
                                            'account_name' => $account['name'],
                                            'group' => $groupName,
                                            'debit' => $account['debit'] ?? 0,
                                            'credit' => $account['credit'] ?? 0,
                                            'balance' => round(($account['debit'] ?? 0) - ($account['credit'] ?? 0), 2),
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        };

        // Income Section
        if (! empty($this->data['incomeTree'])) {
            $rows[] = ['account_name' => 'INCOME', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['incomeTree']);
            $rows[] = [
                'account_name' => 'Total Income',
                'group' => '',
                'debit' => $this->data['totalIncomeDebit'] ?? 0,
                'credit' => $this->data['totalIncomeCredit'] ?? 0,
                'balance' => round(($this->data['totalIncomeDebit'] ?? 0) - ($this->data['totalIncomeCredit'] ?? 0), 2),
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
        }

        // Expenses Section
        if (! empty($this->data['expenseTree'])) {
            $rows[] = ['account_name' => 'EXPENSES', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            $flattenTree($this->data['expenseTree']);
            $rows[] = [
                'account_name' => 'Total Expenses',
                'group' => '',
                'debit' => $this->data['totalExpenseDebit'] ?? 0,
                'credit' => $this->data['totalExpenseCredit'] ?? 0,
                'balance' => round(($this->data['totalExpenseDebit'] ?? 0) - ($this->data['totalExpenseCredit'] ?? 0), 2),
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
        }

        // Uncategorized Section
        if (! empty($this->data['otherTree'])) {
            $rows[] = ['account_name' => 'UNCATEGORIZED', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
            foreach ($this->data['otherTree'] as $account) {
                $rows[] = [
                    'account_name' => $account['name'],
                    'group' => '',
                    'debit' => $account['debit'] ?? 0,
                    'credit' => $account['credit'] ?? 0,
                    'balance' => round(($account['debit'] ?? 0) - ($account['credit'] ?? 0), 2),
                ];
            }
            $rows[] = [
                'account_name' => 'Total Uncategorized',
                'group' => '',
                'debit' => $this->data['totalOtherDebit'] ?? 0,
                'credit' => $this->data['totalOtherCredit'] ?? 0,
                'balance' => round(($this->data['totalOtherDebit'] ?? 0) - ($this->data['totalOtherCredit'] ?? 0), 2),
            ];
            $rows[] = ['account_name' => '', 'group' => '', 'debit' => '', 'credit' => '', 'balance' => ''];
        }

        // Net Profit/Loss
        $totalDebit = $this->data['totalDebit'] ?? 0;
        $totalCredit = $this->data['totalCredit'] ?? 0;
        $netProfit = $this->data['netProfit'] ?? 0;
        $rows[] = [
            'account_name' => $netProfit >= 0 ? 'NET PROFIT' : 'NET LOSS',
            'group' => '',
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'balance' => round($totalDebit - $totalCredit, 2),
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

        $sheet->setCellValue('A1', 'Profit & Loss Statement');
        $sheet->setCellValue('A2', 'Period: '.systemDate($this->startDate).' to '.systemDate($this->endDate));
        if ($this->branchName) {
            $sheet->setCellValue('A3', 'Branch: '.$this->branchName);
        } else {
            $sheet->setCellValue('A3', 'Branch: All Branches');
        }
        $sheet->setCellValue('A4', 'Generated: '.now()->format('d-m-Y H:i:s'));

        $sheet->getStyle('A1:'.self::LAST_COLUMN.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::HEADER_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::HEADER_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

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
            'INCOME' => self::INCOME_COLOR,
            'EXPENSES' => self::EXPENSES_COLOR,
            'UNCATEGORIZED' => self::OTHER_COLOR,
        ];

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $accountName = $sheet->getCell("B{$row}")->getValue();

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

        $sectionHeaders = ['INCOME', 'EXPENSES', 'UNCATEGORIZED'];
        $totalKeywords = ['Total Income', 'Total Expenses', 'Total Uncategorized', 'NET PROFIT', 'NET LOSS'];

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $group = trim($sheet->getCell("A{$row}")->getValue() ?? '');
            $accountName = trim($sheet->getCell("B{$row}")->getValue() ?? '');

            if (in_array($accountName, $sectionHeaders) || in_array($accountName, $totalKeywords)) {
                continue;
            }

            if (empty($group) && ! empty($accountName)) {
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::SECTION_BG],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
            } elseif (! empty($group) && ! empty($accountName)) {
                $isGroupHeader = false;
                if ($row < $highestRow) {
                    $nextRowGroup = trim($sheet->getCell('A'.($row + 1))->getValue() ?? '');
                    if ($nextRowGroup === $accountName) {
                        $isGroupHeader = true;
                    }
                }

                if ($isGroupHeader) {
                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '000000']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F5F5F5'],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    ]);
                } else {
                    $isEvenRow = ($row % 2 === 0);
                    $bgColor = $isEvenRow ? 'FAFAFA' : 'FFFFFF';

                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '000000']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                    ]);

                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                    ]);

                    $sheet->getStyle("C{$row}:E{$row}")->applyFromArray([
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
            $accountName = $sheet->getCell("B{$row}")->getValue();

            if (str_starts_with($accountName, 'Total ') || $accountName === 'NET PROFIT' || $accountName === 'NET LOSS') {
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
            'vertical' => Alignment::VERTICAL_CENTER,
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:B{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->getStyle("C{$headerRow}:E{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(25);
    }

    protected function addBorders(Worksheet $sheet): void
    {
        $dataStartRow = self::HEADER_ROW_COUNT + 2;
        $highestRow = $sheet->getHighestRow();

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
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
    }
}
