<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DayBookReportExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public Builder $query) {}

    public function query()
    {
        return $this->query
            ->orderBy('journal_entries.date', 'asc')
            ->select(
                'journal_entries.id',
                'journal_entries.date',
                'accounts.name as account_name',
                'journal_entries.description',
                'journal_entries.reference_number',
                'journal_entries.remarks',
                'journal_entries.journal_remarks',
                'journal_entries.debit',
                'journal_entries.credit'
            )
            ->orderBy('journal_entries.id', 'asc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Account',
            'Description',
            'Reference',
            'Remarks',
            'Journal Remarks',
            'Debit',
            'Credit',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function map($row): array
    {
        return [$row->id, systemDate($row->date), $row->account_name, $row->description, $row->reference_number ?? '', $row->remarks ?? '', $row->journal_remarks ?? '', $row->debit, $row->credit];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $totalRows = $sheet->getHighestRow() + 1;
                $sheet->getStyle("A{$totalRows}:I{$totalRows}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                $endRow = $totalRows - 1;
                $sheet->setCellValue("H{$totalRows}", "=SUM(H2:H{$endRow})");
                $sheet->setCellValue("I{$totalRows}", "=SUM(I2:I{$endRow})");
            },
        ];
    }
}
