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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
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

    private const GROSS_SECTION_BG = 'ECF0F1'; // Soft light gray

    private const NET_SECTION_BG = 'E8EDF1'; // Cool light gray-blue

    private const TOTAL_BG = '34495E'; // Professional dark gray-blue

    private const TOTAL_TEXT = 'FFFFFF'; // White

    private const PROFIT_COLOR = '27AE60'; // Professional green

    private const LOSS_COLOR = 'E74C3C'; // Professional red

    private const GRAY_FILL = 'F8F9FA'; // Very light gray

    private const DIRECT_EXPENSE_BG = 'FDF2E9'; // Soft warm beige

    private const DIRECT_INCOME_BG = 'FDF2E9'; // Soft warm beige (same as expense)

    private const INDIRECT_EXPENSE_BG = 'FDF2E9'; // Soft warm beige

    private const INDIRECT_INCOME_BG = 'FDF2E9'; // Soft warm beige (same as expense)

    private const COLUMN_HEADER_BG = '34495E'; // Professional dark gray-blue

    private const COLUMN_HEADER_TEXT = 'FFFFFF'; // White

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

        // Top Section: Gross Profit/Loss
        $rows[] = ['section' => 'GROSS PROFIT/LOSS CALCULATION', 'particulars' => '', 'amount' => '', 'particulars_right' => '', 'amount_right' => ''];
        $rows[] = ['section' => '', 'particulars' => 'OPENING STOCK', 'amount' => $this->data['openingStock'], 'particulars_right' => 'NET SALE', 'amount_right' => $this->data['netSale']];
        $rows[] = ['section' => '', 'particulars' => 'NET PURCHASE', 'amount' => $this->data['netPurchase'], 'particulars_right' => 'CLOSING STOCK', 'amount_right' => $this->data['closingStock']];

        // Direct Expense
        $rows[] = ['section' => '', 'particulars' => 'DIRECT EXPENSE', 'amount' => $this->data['directExpense'], 'particulars_right' => 'DIRECT INCOME', 'amount_right' => $this->data['directIncome']];

        // Add Direct Expense details
        $directExpenseMaster = collect($this->data['directExpenseStructure'])->firstWhere('name', 'Direct Expense');
        if ($directExpenseMaster) {
            foreach ($directExpenseMaster['groups'] as $group) {
                if ($group['total'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '  '.$group['name'], 'amount' => $group['total'], 'particulars_right' => '', 'amount_right' => ''];
                    foreach ($group['accounts'] as $account) {
                        if ($account['amount'] > 0) {
                            $rows[] = ['section' => '', 'particulars' => '    '.$account['name'], 'amount' => $account['amount'], 'particulars_right' => '', 'amount_right' => ''];
                        }
                    }
                }
            }
            foreach ($directExpenseMaster['directAccounts'] as $account) {
                if ($account['amount'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '  '.$account['name'], 'amount' => $account['amount'], 'particulars_right' => '', 'amount_right' => ''];
                }
            }
        }

        // Add Direct Income details
        $directIncomeMaster = collect($this->data['directIncomeStructure'])->firstWhere('name', 'Direct Income');
        if ($directIncomeMaster) {
            foreach ($directIncomeMaster['groups'] as $group) {
                if ($group['total'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '  '.$group['name'], 'amount_right' => $group['total']];
                    foreach ($group['accounts'] as $account) {
                        if ($account['amount'] > 0) {
                            $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '    '.$account['name'], 'amount_right' => $account['amount']];
                        }
                    }
                }
            }
            foreach ($directIncomeMaster['directAccounts'] as $account) {
                if ($account['amount'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '  '.$account['name'], 'amount_right' => $account['amount']];
                }
            }
        }

        // Gross Profit/Loss
        if ($this->data['grossProfit'] > 0) {
            $rows[] = ['section' => '', 'particulars' => 'GROSS PROFIT C/D', 'amount' => $this->data['grossProfit'], 'particulars_right' => '', 'amount_right' => ''];
        } elseif ($this->data['grossLoss'] > 0) {
            $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => 'GROSS LOSS C/D', 'amount_right' => $this->data['grossLoss']];
        }

        $rows[] = ['section' => '', 'particulars' => 'TOTAL', 'amount' => $this->data['leftTotal1'], 'particulars_right' => 'TOTAL', 'amount_right' => $this->data['rightTotal1']];

        // Bottom Section: Net Profit/Loss
        $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '', 'amount_right' => ''];
        $rows[] = ['section' => 'NET PROFIT/LOSS CALCULATION', 'particulars' => '', 'amount' => '', 'particulars_right' => '', 'amount_right' => ''];

        if ($this->data['grossLoss'] > 0) {
            $rows[] = ['section' => '', 'particulars' => 'GROSS LOSS B/D', 'amount' => $this->data['grossLoss'], 'particulars_right' => '', 'amount_right' => ''];
        }
        if ($this->data['grossProfit'] > 0) {
            $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => 'GROSS PROFIT B/D', 'amount_right' => $this->data['grossProfit']];
        }

        // Indirect Expense
        $rows[] = ['section' => '', 'particulars' => 'INDIRECT EXPENSE', 'amount' => $this->data['indirectExpense'], 'particulars_right' => 'INDIRECT INCOME', 'amount_right' => $this->data['indirectIncome']];

        // Add Indirect Expense details
        $indirectExpenseMaster = collect($this->data['directExpenseStructure'])->firstWhere('name', 'Indirect Expense');
        if ($indirectExpenseMaster) {
            foreach ($indirectExpenseMaster['groups'] as $group) {
                if ($group['total'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '  '.$group['name'], 'amount' => $group['total'], 'particulars_right' => '', 'amount_right' => ''];
                    foreach ($group['accounts'] as $account) {
                        if ($account['amount'] > 0) {
                            $rows[] = ['section' => '', 'particulars' => '    '.$account['name'], 'amount' => $account['amount'], 'particulars_right' => '', 'amount_right' => ''];
                        }
                    }
                }
            }
            foreach ($indirectExpenseMaster['directAccounts'] as $account) {
                if ($account['amount'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '  '.$account['name'], 'amount' => $account['amount'], 'particulars_right' => '', 'amount_right' => ''];
                }
            }
        }

        // Add Indirect Income details
        $indirectIncomeMaster = collect($this->data['directIncomeStructure'])->firstWhere('name', 'Indirect Income');
        if ($indirectIncomeMaster) {
            foreach ($indirectIncomeMaster['groups'] as $group) {
                if ($group['total'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '  '.$group['name'], 'amount_right' => $group['total']];
                    foreach ($group['accounts'] as $account) {
                        if ($account['amount'] > 0) {
                            $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '    '.$account['name'], 'amount_right' => $account['amount']];
                        }
                    }
                }
            }
            foreach ($indirectIncomeMaster['directAccounts'] as $account) {
                if ($account['amount'] > 0) {
                    $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => '  '.$account['name'], 'amount_right' => $account['amount']];
                }
            }
        }

        // Net Profit/Loss
        if ($this->data['netProfitAmount'] > 0) {
            $rows[] = ['section' => '', 'particulars' => 'NET PROFIT C/D', 'amount' => $this->data['netProfitAmount'], 'particulars_right' => '', 'amount_right' => ''];
        } elseif ($this->data['netLossAmount'] > 0) {
            $rows[] = ['section' => '', 'particulars' => '', 'amount' => '', 'particulars_right' => 'NET LOSS C/D', 'amount_right' => $this->data['netLossAmount']];
        }

        $rows[] = ['section' => '', 'particulars' => 'TOTAL', 'amount' => $this->data['leftTotal2'], 'particulars_right' => 'TOTAL', 'amount_right' => $this->data['rightTotal2']];

        return new Collection($rows);
    }

    public function headings(): array
    {
        return ['Section', 'Particulars', 'Amount', 'Particulars', 'Amount'];
    }

    public function map($row): array
    {
        return [
            $row['section'] ?? '',
            $row['particulars'] ?? '',
            $row['amount'] ?? '',
            $row['particulars_right'] ?? '',
            $row['amount_right'] ?? '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
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
                $this->styleTotalRows($sheet);
                $this->addBorders($sheet);
                $this->autoSizeColumns($sheet);
            },
        ];
    }

    protected function addHeaderRows(Worksheet $sheet): void
    {
        $sheet->insertNewRowBefore(self::HEADER_ROW, self::HEADER_ROW_COUNT);

        $sheet->setCellValue('A1', 'Profit & Loss Report');
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

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $sectionValue = $sheet->getCell("A{$row}")->getValue();
            $particularsValue = $sheet->getCell("B{$row}")->getValue();
            $particularsRightValue = $sheet->getCell("D{$row}")->getValue();
            $amountValue = $sheet->getCell("C{$row}")->getValue();
            $amountRightValue = $sheet->getCell("E{$row}")->getValue();

            // Style section headers (GROSS PROFIT/LOSS CALCULATION, NET PROFIT/LOSS CALCULATION)
            if (! empty($sectionValue) && str_contains($sectionValue, 'CALCULATION')) {
                $bgColor = str_contains($sectionValue, 'GROSS') ? self::GROSS_SECTION_BG : self::NET_SECTION_BG;
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor],
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

            // Style major categories (DIRECT EXPENSE, DIRECT INCOME, INDIRECT EXPENSE, INDIRECT INCOME)
            if ($particularsValue === 'DIRECT EXPENSE') {
                $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::DIRECT_EXPENSE_BG],
                    ],
                ]);
            } elseif ($particularsValue === 'INDIRECT EXPENSE') {
                $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::INDIRECT_EXPENSE_BG],
                    ],
                ]);
            }

            if ($particularsRightValue === 'DIRECT INCOME') {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::DIRECT_INCOME_BG],
                    ],
                ]);
            } elseif ($particularsRightValue === 'INDIRECT INCOME') {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::INDIRECT_INCOME_BG],
                    ],
                ]);
            }

            // Style profit amounts (green) - both label and amount
            if (in_array($particularsValue, ['GROSS PROFIT C/D', 'NET PROFIT C/D'])) {
                $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => self::PROFIT_COLOR]],
                ]);
            }

            if (in_array($particularsRightValue, ['GROSS PROFIT B/D'])) {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => self::PROFIT_COLOR]],
                ]);
            }

            // Style loss amounts (red) - both label and amount
            if (in_array($particularsRightValue, ['GROSS LOSS C/D', 'NET LOSS C/D'])) {
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => self::LOSS_COLOR]],
                ]);
            }

            if (in_array($particularsValue, ['GROSS LOSS B/D'])) {
                $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => self::LOSS_COLOR]],
                ]);
            }
        }
    }

    protected function styleTotalRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        for ($row = self::HEADER_ROW_COUNT + 2; $row <= $highestRow; $row++) {
            $particularsValue = $sheet->getCell("B{$row}")->getValue();
            if ($particularsValue === 'TOTAL') {
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

        // Add thicker border between left and right sections
        $sheet->getStyle("C{$dataStartRow}:C{$highestRow}")->applyFromArray([
            'borders' => [
                'right' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
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
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(18);
    }
}
