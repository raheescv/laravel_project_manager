<?php

namespace App\Exports;

use App\Models\Models\Views\Ledger;
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

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = Ledger::select('id', 'date', 'account_name', 'description', 'reference_number', 'remarks', 'debit', 'credit', 'balance')
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['to_date'] ?? '', function ($query, $value) {
                $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['branch_id'] ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->filters['account_id'] ?? '', function ($query, $value) {
                $query->where('account_id', $value);
            });

        return $query;
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
            'Debit',
            'Credit',
            'Balance',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function map($row): array
    {
        return [
            $row->id,
            systemDate($row->date),
            $row->account_name,
            $row->description,
            $row->reference_number,
            $row->remarks,
            $row->debit,
            $row->credit,
            $row->balance,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalRows = $sheet->getHighestRow() + 1;
                $sheet->getStyle("A{$totalRows}:O{$totalRows}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                $endRow = $totalRows - 1;
                $sheet->setCellValue("G{$totalRows}", "=SUM(G2:G{$endRow})");
                $sheet->setCellValue("H{$totalRows}", "=SUM(H2:H{$endRow})");
            },
        ];
    }
}
