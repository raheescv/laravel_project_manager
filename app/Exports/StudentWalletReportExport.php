<?php

namespace App\Exports;

use App\Livewire\Report\Student\WalletReport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** The student wallet report as filtered on screen (WalletReport::filteredQuery). */
class StudentWalletReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return WalletReport::filteredQuery($this->filters);
    }

    public function headings(): array
    {
        return ['Admission No', 'Student', 'Grade', 'Section', 'Status', 'Card', 'Card Status', 'Opening', 'Added', 'Spent', 'Closing Balance'];
    }

    public function map($row): array
    {
        return [
            $row->admission_no,
            $row->name,
            $row->grade,
            $row->section,
            $row->student_status,
            $row->card_uid,
            $row->card_uid ? $row->card_status : '',
            round((float) $row->opening_balance, 2),
            round((float) $row->period_in, 2),
            round((float) $row->period_out, 2),
            round((float) $row->closing_balance, 2),
        ];
    }
}
