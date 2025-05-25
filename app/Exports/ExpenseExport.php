<?php

namespace App\Exports;

use App\Models\JournalEntry;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExpenseExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = JournalEntry::expenseList($this->filters)
            ->with('account:id,name', 'journal:id,date,description');

        return $query;
    }

    public function headings(): array
    {
        return [
            'id',
            'date',
            'account name',
            'payee',
            'reference number',
            'journal description',
            'description',
            'amount',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function map($row): array
    {
        return [
            $row->journal->id,
            systemDate($row->journal->date),
            $row->account->name,
            $row->person_name,
            $row->reference_number,
            $row->journal->description,
            $row->description,
            $row->debit,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $totalRows = $sheet->getHighestRow() + 1;
                $sheet->getStyle("A{$totalRows}:O{$totalRows}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                $endRow = $totalRows - 1;
                $sheet->setCellValue("A{$totalRows}", 'Total');
                $sheet->setCellValue("H{$totalRows}", "=SUM(H2:H{$endRow})");
            },
        ];
    }
}
