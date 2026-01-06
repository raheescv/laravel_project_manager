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

    private const HEADER_ROW_COUNT = 3;

    private const HEADER_ROW = 1;

    private const OPENING_BALANCE_ROW = 5;

    private const GRAY_FILL = 'E9ECEF';

    private const LAST_COLUMN = 'I';

    public function __construct(Account $account, array $filters = [], bool $excludeOpeningFromTotal = false)
    {
        $this->account = $account;
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
        return JournalEntry::with('account')
            ->where('counter_account_id', $this->account->id)
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<', date('Y-m-d', strtotime($value)));
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

    protected function getTransactionsQuery()
    {
        return JournalEntry::with(['account', 'journal'])
            ->where('counter_account_id', $this->account->id)
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
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['to_date'] ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
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
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $this->addHeaderRows($sheet);
                $this->styleOpeningBalanceRow($sheet);
                $this->addTotalRows($sheet);
                $this->autoSizeColumns($sheet);
            },
        ];
    }

    protected function addHeaderRows(Worksheet $sheet): void
    {
        $sheet->insertNewRowBefore(self::HEADER_ROW, self::HEADER_ROW_COUNT);

        $sheet->setCellValue('A1', 'Account Ledger Report');
        $sheet->setCellValue('A2', 'Account: '.$this->account->name);
        $sheet->setCellValue('A3', $this->getPeriodString());

        $sheet->getStyle('A1:'.self::LAST_COLUMN.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
        ]);

        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->mergeCells('A1:'.self::LAST_COLUMN.'1');
        $sheet->mergeCells('A2:'.self::LAST_COLUMN.'2');
        $sheet->mergeCells('A3:'.self::LAST_COLUMN.'3');
    }

    protected function getPeriodString(): string
    {
        $fromDate = $this->filters['from_date'] ?? 'All';
        $toDate = $this->filters['to_date'] ?? 'All';

        return "Period: {$fromDate} to {$toDate}";
    }

    protected function styleOpeningBalanceRow(Worksheet $sheet): void
    {
        $sheet->getStyle('A'.self::OPENING_BALANCE_ROW.':'.self::LAST_COLUMN.self::OPENING_BALANCE_ROW)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::GRAY_FILL],
            ],
        ]);
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
        $sheet->setCellValue("F{$totalRow}", 'Total');

        $startRow = $this->excludeOpeningFromTotal ? self::OPENING_BALANCE_ROW + 1 : self::OPENING_BALANCE_ROW;
        $sheet->setCellValue("G{$totalRow}", "=SUM(G{$startRow}:G{$highestRow})");
        $sheet->setCellValue("H{$totalRow}", "=SUM(H{$startRow}:H{$highestRow})");

        $this->applyRowStyle($sheet, $totalRow);
    }

    protected function addBalanceRow(Worksheet $sheet, int $balanceRow, int $totalRow): void
    {
        $sheet->setCellValue("F{$balanceRow}", 'Balance');

        // Build formula without the = sign to avoid double equals in IF statement
        $balanceFormula = "G{$totalRow}-H{$totalRow}";
        $sheet->setCellValue("G{$balanceRow}", "=IF({$balanceFormula}>0,{$balanceFormula},\"\")");
        $sheet->setCellValue("H{$balanceRow}", "=IF({$balanceFormula}<0,ABS({$balanceFormula}),\"\")");

        $this->applyRowStyle($sheet, $balanceRow);
    }

    protected function applyRowStyle(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle("A{$row}:".self::LAST_COLUMN."{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::GRAY_FILL],
            ],
        ]);
    }

    protected function autoSizeColumns(Worksheet $sheet): void
    {
        foreach (range('A', self::LAST_COLUMN) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
