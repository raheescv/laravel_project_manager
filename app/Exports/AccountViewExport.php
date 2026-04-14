<?php

namespace App\Exports;

use App\Models\Account;
use App\Models\JournalEntry;
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

class AccountViewExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected Account $account;

    protected array $filters;

    protected bool $excludeOpeningFromTotal;

    protected array $openingBalance;

    protected float $runningBalance;

    // Layout constants
    private const HEADER_ROW_COUNT = 7;

    private const HEADER_ROW = 1;

    private const COLUMN_HEADER_ROW = 8;

    private const OPENING_BALANCE_ROW = 9;

    private const LAST_COLUMN = 'I';

    // Color palette
    private const PRIMARY_BG = '2C3E50';

    private const PRIMARY_TEXT = 'FFFFFF';

    private const ACCENT_BG = '34495E';

    private const ACCENT_TEXT = 'FFFFFF';

    private const INFO_BG = 'EBF5FB';

    private const INFO_BORDER = '3498DB';

    private const OPENING_BG = 'FEF9E7';

    private const OPENING_BORDER = 'F39C12';

    private const TOTAL_BG = '2C3E50';

    private const TOTAL_TEXT = 'FFFFFF';

    private const BALANCE_BG = '1ABC9C';

    private const BALANCE_TEXT = 'FFFFFF';

    private const DEBIT_COLOR = 'E74C3C';

    private const CREDIT_COLOR = '27AE60';

    private const EVEN_ROW_BG = 'F8F9FA';

    private const ODD_ROW_BG = 'FFFFFF';

    private const BORDER_COLOR = 'DEE2E6';

    // Account type colors
    private const TYPE_COLORS = [
        'asset' => '3498DB',
        'liability' => 'E67E22',
        'equity' => '9B59B6',
        'income' => '27AE60',
        'expense' => 'E74C3C',
    ];

    public function __construct(Account $account, array $filters = [], bool $excludeOpeningFromTotal = false)
    {
        $this->account = $account->load(['accountCategory', 'customerType']);
        $this->filters = $filters;
        $this->excludeOpeningFromTotal = $excludeOpeningFromTotal;
        $this->calculateOpeningBalance();
    }

    protected function calculateOpeningBalance(): void
    {
        if ($this->excludeOpeningFromTotal) {
            $this->openingBalance = ['debit' => 0, 'credit' => 0];
            $this->runningBalance = 0;

            return;
        }

        $openingBalance = $this->getOpeningBalanceQuery()->first();

        $this->openingBalance = [
            'debit' => ($openingBalance->debit ?? 0) + ($this->account->opening_debit ?? 0),
            'credit' => ($openingBalance->credit ?? 0) + ($this->account->opening_credit ?? 0),
        ];

        $this->runningBalance = $this->openingBalance['debit'] - $this->openingBalance['credit'];
    }

    protected function getOpeningBalanceQuery()
    {
        return $this->getBaseQuery()
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                return $query->whereDate('date', '<', date('Y-m-d', strtotime($value)));
            })
            ->selectRaw('ROUND(SUM(debit),2) as debit, ROUND(SUM(credit),2) as credit');
    }

    public function collection(): Collection
    {
        $transactions = $this->getTransactionsQuery()->get();
        $collection = new Collection();

        // Add opening balance row
        $collection->push($this->createOpeningBalanceRow());

        // Add transaction rows with running balance
        foreach ($transactions as $item) {
            $this->runningBalance += $item->debit - $item->credit;
            $collection->push($this->createTransactionRow($item));
        }

        return $collection;
    }

    protected function getBaseQuery()
    {
        return JournalEntry::where('account_id', $this->account->id)
            ->when($this->filters['branch_id'] ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            });
    }

    protected function getTransactionsQuery()
    {
        return $this->getBaseQuery()
            ->with(['account', 'journal'])
            ->when($this->filters['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q
                        ->where('description', 'like', "%{$value}%")
                        ->orWhere('journal_entries.reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_entries.journal_remarks', 'like', "%{$value}%")
                        ->orWhere('journal_entries.remarks', 'like', "%{$value}%");
                });
            })
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                return $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['to_date'] ?? '', function ($query, $value) {
                return $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['filter_account_id'] ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->orderBy('date', 'ASC')
            ->orderBy('id', 'ASC');
    }

    protected function createOpeningBalanceRow(): object
    {
        return (object) [
            'type' => 'opening',
            'journal_id' => '',
            'date' => '',
            'account_name' => '',
            'payee' => '',
            'reference_number' => '',
            'description' => 'Opening Balance',
            'debit' => $this->openingBalance['debit'],
            'credit' => $this->openingBalance['credit'],
            'balance' => $this->runningBalance,
        ];
    }

    protected function createTransactionRow(JournalEntry $item): object
    {
        return (object) [
            'type' => 'transaction',
            'journal_id' => $item->journal_id,
            'date' => $item->journal->date ?? '',
            'account_name' => $item->account->name ?? '',
            'payee' => $item->person_name ?? '',
            'reference_number' => $item->reference_number ?? '',
            'description' => $item->description ?? '',
            'debit' => $item->debit ?? 0,
            'credit' => $item->credit ?? 0,
            'balance' => $this->runningBalance,
        ];
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Account Name', 'Payee', 'Reference No', 'Description', 'Debit', 'Credit', 'Balance'];
    }

    public function map($row): array
    {
        return [$row->journal_id ?: '', $row->date ? systemDate($row->date) : '', $row->account_name ?? '', $row->payee ?? '', $row->reference_number ?? '', $row->description ?? '', $this->formatAmount($row->debit), $this->formatAmount($row->credit), $this->formatAmount($row->balance)];
    }

    protected function formatAmount($value): float|string
    {
        return $value != 0 ? (float) $value : '';
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            self::COLUMN_HEADER_ROW => [
                'font' => ['bold' => true, 'color' => ['rgb' => self::ACCENT_TEXT]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::ACCENT_BG],
                ],
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
                $this->styleOpeningBalanceRow($sheet);
                $this->styleDataRows($sheet);
                $this->addTotalRows($sheet);
                $this->addBorders($sheet);
                $this->setColumnWidths($sheet);
            },
        ];
    }

    protected function getAccountTypeLabel(): string
    {
        return ucfirst($this->account->account_type ?? 'N/A');
    }

    protected function getAccountTypeColor(): string
    {
        $type = strtolower($this->account->account_type ?? '');

        return self::TYPE_COLORS[$type] ?? '95A5A6';
    }

    protected function getCategoryName(): string
    {
        return $this->account->accountCategory->name ?? 'Uncategorized';
    }

    protected function addHeaderRows(Worksheet $sheet): void
    {
        $sheet->insertNewRowBefore(self::HEADER_ROW, self::HEADER_ROW_COUNT);

        // Row 1: Report Title
        $sheet->setCellValue('A1', 'ACCOUNT LEDGER REPORT');

        // Row 2: Account Name
        $sheet->setCellValue('A2', $this->account->name);

        // Row 3: Account Type + Category
        $accountType = $this->getAccountTypeLabel();
        $category = $this->getCategoryName();
        $customerType = $this->account->customerType->name ?? '';
        $typeInfo = "Type: {$accountType}    |    Category: {$category}";
        if ($customerType) {
            $typeInfo .= "    |    Customer Type: {$customerType}";
        }
        $sheet->setCellValue('A3', $typeInfo);

        // Row 4: Contact / Details
        $details = [];
        if ($this->account->mobile) {
            $details[] = 'Mobile: '.$this->account->mobile;
        }
        if ($this->account->email) {
            $details[] = 'Email: '.$this->account->email;
        }
        if ($this->account->place) {
            $details[] = 'Place: '.$this->account->place;
        }
        if ($this->account->company) {
            $details[] = 'Company: '.$this->account->company;
        }
        $sheet->setCellValue('A4', implode('    |    ', $details));

        // Row 5: Period
        $sheet->setCellValue('A5', $this->getPeriodString());

        // Row 6: Generated timestamp
        $sheet->setCellValue('A6', 'Generated: '.now()->format('d-m-Y H:i:s'));

        // Row 7: Empty spacer
        $sheet->setCellValue('A7', '');

        // ---- Style Row 1: Title ----
        $sheet->getStyle('A1:'.self::LAST_COLUMN.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::PRIMARY_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::PRIMARY_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        // ---- Style Row 2: Account Name ----
        $sheet->getStyle('A2:'.self::LAST_COLUMN.'2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => self::PRIMARY_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $this->getAccountTypeColor()],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(30);

        // ---- Style Row 3: Type / Category ----
        $sheet->getStyle('A3:'.self::LAST_COLUMN.'3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::INFO_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::INFO_BORDER]],
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // ---- Style Row 4: Contact Details ----
        $sheet->getStyle('A4:'.self::LAST_COLUMN.'4')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '555555']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::INFO_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // ---- Style Row 5: Period ----
        $sheet->getStyle('A5:'.self::LAST_COLUMN.'5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(20);

        // ---- Style Row 6: Generated ----
        $sheet->getStyle('A6:'.self::LAST_COLUMN.'6')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '999999']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(18);

        // Row 7: spacer
        $sheet->getRowDimension(7)->setRowHeight(6);
        $sheet->getStyle('A7:'.self::LAST_COLUMN.'7')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::PRIMARY_BG],
            ],
        ]);

        // Merge cells for header rows
        for ($i = 1; $i <= self::HEADER_ROW_COUNT; $i++) {
            $sheet->mergeCells("A{$i}:".self::LAST_COLUMN."{$i}");
        }
    }

    protected function getPeriodString(): string
    {
        $fromDate = $this->filters['from_date'] ?? 'All';
        $toDate = $this->filters['to_date'] ?? 'All';

        if ($fromDate !== 'All') {
            $fromDate = systemDate($fromDate);
        }
        if ($toDate !== 'All') {
            $toDate = systemDate($toDate);
        }

        return "Period: {$fromDate}  to  {$toDate}";
    }

    protected function styleColumnHeaders(Worksheet $sheet): void
    {
        $row = self::COLUMN_HEADER_ROW;

        $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::ACCENT_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::ACCENT_BG],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1A252F'],
                ],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(25);
    }

    protected function styleOpeningBalanceRow(Worksheet $sheet): void
    {
        $row = self::OPENING_BALANCE_ROW;

        $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '856404']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::OPENING_BG],
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::OPENING_BORDER]],
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::OPENING_BORDER]],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    protected function styleDataRows(Worksheet $sheet): void
    {
        $dataStartRow = self::OPENING_BALANCE_ROW + 1;
        $highestRow = $sheet->getHighestRow();

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $isEven = (($row - $dataStartRow) % 2 === 0);
            $bgColor = $isEven ? self::EVEN_ROW_BG : self::ODD_ROW_BG;

            $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'font' => ['size' => 10],
            ]);

            // Color debit values red
            $debitValue = $sheet->getCell("G{$row}")->getValue();
            if (is_numeric($debitValue) && $debitValue > 0) {
                $sheet->getStyle("G{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => self::DEBIT_COLOR], 'bold' => true],
                ]);
            }

            // Color credit values green
            $creditValue = $sheet->getCell("H{$row}")->getValue();
            if (is_numeric($creditValue) && $creditValue > 0) {
                $sheet->getStyle("H{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => self::CREDIT_COLOR], 'bold' => true],
                ]);
            }

            // Style balance column
            $balanceValue = $sheet->getCell("I{$row}")->getValue();
            if (is_numeric($balanceValue)) {
                $balanceColor = $balanceValue >= 0 ? '2C3E50' : self::DEBIT_COLOR;
                $sheet->getStyle("I{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $balanceColor]],
                ]);
            }

            // Right-align amount columns
            $sheet->getStyle("G{$row}:I{$row}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
        }
    }

    protected function addTotalRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $totalRow = $highestRow + 1;
        $balanceRow = $totalRow + 1;

        $this->addTotalRow($sheet, $totalRow, $highestRow);
        $this->addBalanceRow($sheet, $balanceRow, $totalRow);
    }

    protected function addTotalRow(Worksheet $sheet, int $totalRow, int $highestRow): void
    {
        $sheet->setCellValue("F{$totalRow}", 'TOTAL');

        $startRow = $this->excludeOpeningFromTotal ? self::OPENING_BALANCE_ROW + 1 : self::OPENING_BALANCE_ROW;
        $sheet->setCellValue("G{$totalRow}", "=SUM(G{$startRow}:G{$highestRow})");
        $sheet->setCellValue("H{$totalRow}", "=SUM(H{$startRow}:H{$highestRow})");

        $sheet->getStyle("A{$totalRow}:".self::LAST_COLUMN."{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::TOTAL_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::TOTAL_BG],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->getStyle("F{$totalRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension($totalRow)->setRowHeight(25);
    }

    protected function addBalanceRow(Worksheet $sheet, int $balanceRow, int $totalRow): void
    {
        $sheet->setCellValue("F{$balanceRow}", 'BALANCE');

        $balanceFormula = "G{$totalRow}-H{$totalRow}";
        $sheet->setCellValue("G{$balanceRow}", "=IF({$balanceFormula}>0,{$balanceFormula},\"\")");
        $sheet->setCellValue("H{$balanceRow}", "=IF({$balanceFormula}<0,ABS({$balanceFormula}),\"\")");

        $sheet->getStyle("A{$balanceRow}:".self::LAST_COLUMN."{$balanceRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::BALANCE_TEXT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::BALANCE_BG],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->getStyle("F{$balanceRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension($balanceRow)->setRowHeight(25);
    }

    protected function addBorders(Worksheet $sheet): void
    {
        $dataStartRow = self::OPENING_BALANCE_ROW;
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A{$dataStartRow}:".self::LAST_COLUMN."{$highestRow}")->applyFromArray([
            'borders' => [
                'vertical' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER_COLOR],
                ],
                'horizontal' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER_COLOR],
                ],
            ],
        ]);

        // Outer border
        $sheet->getStyle('A'.self::COLUMN_HEADER_ROW.':'.self::LAST_COLUMN."{$highestRow}")->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => self::PRIMARY_BG],
                ],
            ],
        ]);
    }

    protected function setColumnWidths(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(8);   // #
        $sheet->getColumnDimension('B')->setWidth(14);  // Date
        $sheet->getColumnDimension('C')->setWidth(28);  // Account Name
        $sheet->getColumnDimension('D')->setWidth(20);  // Payee
        $sheet->getColumnDimension('E')->setWidth(16);  // Reference No
        $sheet->getColumnDimension('F')->setWidth(30);  // Description
        $sheet->getColumnDimension('G')->setWidth(16);  // Debit
        $sheet->getColumnDimension('H')->setWidth(16);  // Credit
        $sheet->getColumnDimension('I')->setWidth(16);  // Balance
    }
}
