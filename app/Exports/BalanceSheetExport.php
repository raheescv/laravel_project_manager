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

class BalanceSheetExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $data;

    protected string $endDate;

    protected ?string $branchName;

    private const HEADER_ROW_COUNT = 4;

    private const HEADER_ROW = 1;

    private const LAST_COLUMN = 'C';

    // Color constants - Premium corporate palette
    private const HEADER_BG = '1B2A4A';

    private const HEADER_TEXT = 'FFFFFF';

    private const GRAY_FILL = 'F4F6F9';

    private const COLUMN_HEADER_BG = '2C3E50';

    private const COLUMN_HEADER_TEXT = 'FFFFFF';

    // Section colors
    private const ASSETS_COLOR = '2980B9';

    private const LIABILITIES_COLOR = 'E67E22';

    private const EQUITY_COLOR = '27AE60';

    // Sub-section colors
    private const SUB_SECTION_BG = 'EBF5FB';

    private const LIAB_SUB_SECTION_BG = 'FEF5E7';

    private const EQUITY_SUB_SECTION_BG = 'EAFAF1';

    // Total colors
    private const SECTION_TOTAL_BG = '34495E';

    private const SECTION_TOTAL_TEXT = 'FFFFFF';

    private const SUB_TOTAL_BG = 'D5DBDB';

    private const SUB_TOTAL_TEXT = '2C3E50';

    // Grand total
    private const GRAND_TOTAL_BG = '1B2A4A';

    private const GRAND_TOTAL_TEXT = 'FFFFFF';

    // Category row
    private const CATEGORY_BG = 'F2F3F4';

    // Group row
    private const GROUP_BG = 'FAFAFA';

    public function __construct(array $data, string $endDate, ?string $branchName = null)
    {
        $this->data = $data;
        $this->endDate = $endDate;
        $this->branchName = $branchName;
    }

    public function collection(): Collection
    {
        $rows = [];

        // ══════════ ASSETS ══════════
        $rows[] = $this->sectionHeader('ASSETS');

        // Current Assets
        if (! empty($this->data['currentAssets'])) {
            $rows[] = $this->subSectionHeader('Current Assets');
            $this->flattenCategories($this->data['currentAssets'], $rows);
            $rows[] = $this->subTotalRow('Total Current Assets', $this->data['totalCurrentAssets']);
        }

        // Fixed Assets
        if (! empty($this->data['fixedAssets'])) {
            $rows[] = $this->subSectionHeader('Fixed Assets');
            $this->flattenCategories($this->data['fixedAssets'], $rows);
            $rows[] = $this->subTotalRow('Total Fixed Assets', $this->data['totalFixedAssets']);
        }

        // Other Assets
        if (! empty($this->data['otherAssets'])) {
            $rows[] = $this->subSectionHeader('Other Assets');
            $this->flattenCategories($this->data['otherAssets'], $rows);
            $rows[] = $this->subTotalRow('Total Other Assets', $this->data['totalOtherAssets']);
        }

        $rows[] = $this->sectionTotalRow('TOTAL ASSETS', $this->data['totalAssets']);
        $rows[] = $this->emptyRow();

        // ══════════ LIABILITIES ══════════
        $rows[] = $this->sectionHeader('LIABILITIES');

        // Current Liabilities
        if (! empty($this->data['currentLiabilities'])) {
            $rows[] = $this->subSectionHeader('Current Liabilities');
            $this->flattenCategories($this->data['currentLiabilities'], $rows);
            $rows[] = $this->subTotalRow('Total Current Liabilities', $this->data['totalCurrentLiabilities']);
        }

        // Long Term Liabilities
        if (! empty($this->data['longTermLiabilities'])) {
            $rows[] = $this->subSectionHeader('Long Term Liabilities');
            $this->flattenCategories($this->data['longTermLiabilities'], $rows);
            $rows[] = $this->subTotalRow('Total Long Term Liabilities', $this->data['totalLongTermLiabilities']);
        }

        $rows[] = $this->sectionTotalRow('TOTAL LIABILITIES', $this->data['totalLiabilities']);
        $rows[] = $this->emptyRow();

        // ══════════ EQUITY ══════════
        $rows[] = $this->sectionHeader('EQUITY');

        // Owner's Equity
        if (! empty($this->data['ownerEquity'])) {
            $rows[] = $this->subSectionHeader("Owner's Equity");
            $this->flattenCategories($this->data['ownerEquity'], $rows);
            $rows[] = $this->subTotalRow("Total Owner's Equity", $this->data['totalEquityAccounts']);
        }

        // Retained Earnings
        if (! empty($this->data['retainedEarningAccounts'])) {
            $rows[] = $this->subSectionHeader('Retained Earnings');
            $this->flattenCategories($this->data['retainedEarningAccounts'], $rows);
            $rows[] = $this->subTotalRow('Total Retained Earnings', $this->data['totalRetainedEarnings']);
        }

        $rows[] = $this->sectionTotalRow('TOTAL EQUITY', $this->data['totalEquity']);
        $rows[] = $this->emptyRow();

        // ══════════ GRAND TOTALS ══════════
        $rows[] = $this->grandTotalRow('TOTAL LIABILITIES + EQUITY', round($this->data['totalLiabilities'] + $this->data['totalEquity'], 2));

        // Balance check
        $difference = abs($this->data['totalAssets'] - ($this->data['totalLiabilities'] + $this->data['totalEquity']));
        $isBalanced = $difference < 0.01;
        $rows[] = $this->emptyRow();
        $rows[] = [
            'type' => 'balance_check',
            'group' => '',
            'account' => $isBalanced ? 'BALANCED' : 'UNBALANCED (Difference: '.number_format($difference, 2).')',
            'amount' => '',
        ];

        return new Collection($rows);
    }

    private function sectionHeader(string $name): array
    {
        return ['type' => 'section', 'group' => '', 'account' => $name, 'amount' => ''];
    }

    private function subSectionHeader(string $name): array
    {
        return ['type' => 'sub_section', 'group' => '', 'account' => $name, 'amount' => ''];
    }

    private function subTotalRow(string $label, $amount): array
    {
        return ['type' => 'sub_total', 'group' => '', 'account' => $label, 'amount' => round(abs($amount), 2)];
    }

    private function sectionTotalRow(string $label, $amount): array
    {
        return ['type' => 'section_total', 'group' => '', 'account' => $label, 'amount' => round(abs($amount), 2)];
    }

    private function grandTotalRow(string $label, $amount): array
    {
        return ['type' => 'grand_total', 'group' => '', 'account' => $label, 'amount' => round(abs($amount), 2)];
    }

    private function emptyRow(): array
    {
        return ['type' => 'empty', 'group' => '', 'account' => '', 'amount' => ''];
    }

    private function flattenCategories(array $categories, array &$rows): void
    {
        foreach ($categories as $category) {
            if (! isset($category['name'])) {
                continue;
            }

            // Category header
            $rows[] = [
                'type' => 'category',
                'group' => '',
                'account' => $category['name'],
                'amount' => round(abs($category['total'] ?? 0), 2),
            ];

            // Direct accounts
            if (! empty($category['directAccounts'])) {
                foreach ($category['directAccounts'] as $account) {
                    $rows[] = [
                        'type' => 'account',
                        'group' => $category['name'],
                        'account' => $account['name'],
                        'amount' => round(abs($account['amount'] ?? 0), 2),
                    ];
                }
            }

            // Groups (sub-categories)
            if (! empty($category['groups'])) {
                foreach ($category['groups'] as $group) {
                    $rows[] = [
                        'type' => 'group',
                        'group' => $category['name'],
                        'account' => $group['name'],
                        'amount' => round(abs($group['total'] ?? 0), 2),
                    ];

                    if (! empty($group['accounts'])) {
                        foreach ($group['accounts'] as $account) {
                            $rows[] = [
                                'type' => 'account',
                                'group' => $group['name'],
                                'account' => $account['name'],
                                'amount' => round(abs($account['amount'] ?? 0), 2),
                            ];
                        }
                    }
                }
            }
        }
    }

    public function headings(): array
    {
        return ['Group', 'Account Name', 'Amount'];
    }

    public function map($row): array
    {
        return [
            $row['group'] ?? '',
            $row['account'] ?? '',
            is_numeric($row['amount']) ? $row['amount'] : '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = self::HEADER_ROW_COUNT + 1;

        return [
            $headerRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => self::COLUMN_HEADER_TEXT]],
                'fill' => ['fillType' => Fill::FILL_SOLID],
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
                $this->styleAllDataRows($sheet);
                $this->addBorders($sheet);
                $this->autoSizeColumns($sheet);
                $this->freezePanes($sheet);
            },
        ];
    }

    protected function addHeaderRows(Worksheet $sheet): void
    {
        $sheet->insertNewRowBefore(self::HEADER_ROW, self::HEADER_ROW_COUNT);

        $sheet->setCellValue('A1', 'BALANCE SHEET');
        $sheet->setCellValue('A2', 'As of '.systemDate($this->endDate));
        $sheet->setCellValue('A3', 'Branch: '.($this->branchName ?? 'All Branches'));
        $sheet->setCellValue('A4', 'Generated: '.now()->format('d-m-Y H:i:s'));

        // Title row - dark premium header
        $sheet->getStyle('A1:'.self::LAST_COLUMN.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => self::HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Subtitle rows
        $sheet->getStyle('A2:'.self::LAST_COLUMN.'2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3:'.self::LAST_COLUMN.'4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '7F8C8D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GRAY_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach (range('A', self::LAST_COLUMN) as $col) {
            for ($i = 1; $i <= self::HEADER_ROW_COUNT; $i++) {
                $sheet->mergeCells("A{$i}:".self::LAST_COLUMN."{$i}");
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);
    }

    protected function styleColumnHeaders(Worksheet $sheet): void
    {
        $headerRow = self::HEADER_ROW_COUNT + 1;

        $sheet->getStyle("A{$headerRow}:".self::LAST_COLUMN."{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::COLUMN_HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLUMN_HEADER_BG]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::HEADER_BG]],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:B{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle("C{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(28);
    }

    protected function styleAllDataRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $dataStartRow = self::HEADER_ROW_COUNT + 2;

        $sectionColors = [
            'ASSETS' => self::ASSETS_COLOR,
            'LIABILITIES' => self::LIABILITIES_COLOR,
            'EQUITY' => self::EQUITY_COLOR,
        ];

        $subSectionBgs = [
            'Current Assets' => self::SUB_SECTION_BG,
            'Fixed Assets' => self::SUB_SECTION_BG,
            'Other Assets' => self::SUB_SECTION_BG,
            'Current Liabilities' => self::LIAB_SUB_SECTION_BG,
            'Long Term Liabilities' => self::LIAB_SUB_SECTION_BG,
            "Owner's Equity" => self::EQUITY_SUB_SECTION_BG,
            'Retained Earnings' => self::EQUITY_SUB_SECTION_BG,
        ];

        // We need to track rows by type. Since we can't store metadata in Excel cells,
        // we identify row types by content patterns.
        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $group = trim($sheet->getCell("A{$row}")->getValue() ?? '');
            $accountName = trim($sheet->getCell("B{$row}")->getValue() ?? '');
            $amount = $sheet->getCell("C{$row}")->getValue();

            // Empty rows
            if (empty($accountName) && empty($group)) {
                continue;
            }

            // Section headers (ASSETS, LIABILITIES, EQUITY)
            if (isset($sectionColors[$accountName]) && empty($group)) {
                $color = $sectionColors[$accountName];
                $sheet->mergeCells("A{$row}:".self::LAST_COLUMN."{$row}");
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $color]],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $color]],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(30);

                continue;
            }

            // Sub-section headers (Current Assets, Fixed Assets, etc.)
            if (isset($subSectionBgs[$accountName]) && empty($group) && ! is_numeric($amount)) {
                $bgColor = $subSectionBgs[$accountName];
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '2C3E50']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BDC3C7']],
                        'left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '95A5A6']],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(24);

                continue;
            }

            // Section totals (TOTAL ASSETS, TOTAL LIABILITIES, TOTAL EQUITY)
            if (str_starts_with($accountName, 'TOTAL ') && ! str_starts_with($accountName, 'TOTAL LIABILITIES +')) {
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::SECTION_TOTAL_TEXT]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::SECTION_TOTAL_BG]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '2C3E50']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '2C3E50']],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(26);

                continue;
            }

            // Grand total (TOTAL LIABILITIES + EQUITY)
            if (str_starts_with($accountName, 'TOTAL LIABILITIES +')) {
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => self::GRAND_TOTAL_TEXT]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GRAND_TOTAL_BG]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '000000']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '000000']],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(28);

                continue;
            }

            // Sub-total rows (Total Current Assets, Total Fixed Assets, etc.)
            if (str_starts_with($accountName, 'Total ') && empty($group)) {
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::SUB_TOTAL_TEXT]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::SUB_TOTAL_BG]],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '95A5A6']],
                        'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '95A5A6']],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(22);

                continue;
            }

            // Balance check row
            if ($accountName === 'BALANCED' || str_starts_with($accountName, 'UNBALANCED')) {
                $isBalanced = $accountName === 'BALANCED';
                $sheet->mergeCells("A{$row}:".self::LAST_COLUMN."{$row}");
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isBalanced ? '27AE60' : 'E74C3C']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $isBalanced ? '1E8449' : 'C0392B']],
                    ],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(26);

                continue;
            }

            // Category rows (no group, has amount)
            if (empty($group) && ! empty($accountName) && is_numeric($amount)) {
                $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CATEGORY_BG]],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'D5D8DC']],
                    ],
                ]);

                continue;
            }

            // Group rows (has group, check if next row uses this as group = group header)
            if (! empty($group) && ! empty($accountName)) {
                $isGroupHeader = false;
                if ($row < $highestRow) {
                    $nextRowGroup = trim($sheet->getCell('A'.($row + 1))->getValue() ?? '');
                    if ($nextRowGroup === $accountName) {
                        $isGroupHeader = true;
                    }
                }

                if ($isGroupHeader) {
                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '566573']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GROUP_BG]],
                        'borders' => [
                            'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E5E8E8']],
                        ],
                    ]);
                } else {
                    // Regular account row - alternating zebra
                    $isEvenRow = ($row % 2 === 0);
                    $bgColor = $isEvenRow ? 'FBFCFC' : 'FFFFFF';

                    $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                        'font' => ['size' => 10, 'color' => ['rgb' => '5D6D7E']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    ]);

                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    ]);
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'font' => ['color' => ['rgb' => '2C3E50']],
                    ]);
                }
            }
        }
    }

    protected function addBorders(Worksheet $sheet): void
    {
        $dataStartRow = self::HEADER_ROW_COUNT + 2;
        $highestRow = $sheet->getHighestRow();

        // Outer border around entire data area
        $sheet->getStyle("A{$dataStartRow}:".self::LAST_COLUMN."{$highestRow}")->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2C3E50']],
            ],
        ]);

        // Vertical separators between columns
        $sheet->getStyle("B{$dataStartRow}:B{$highestRow}")->applyFromArray([
            'borders' => [
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D5D8DC']],
            ],
        ]);
    }

    protected function autoSizeColumns(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(48);
        $sheet->getColumnDimension('C')->setWidth(22);
    }

    protected function freezePanes(Worksheet $sheet): void
    {
        $freezeRow = self::HEADER_ROW_COUNT + 2;
        $sheet->freezePane("A{$freezeRow}");
    }
}
