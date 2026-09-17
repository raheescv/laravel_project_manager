<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportTemplate implements FromArray, WithHeadings, WithStyles
{
    public const HEADINGS = [
        'admission_no', 'name', 'grade', 'section', 'gender', 'dob', 'qid', 'nationality', 'student_mobile', 'student_email', 'status', 'card_uid',
        'parent_name', 'parent_mobile', 'parent_email', 'parent_relation',
        'second_parent_name', 'second_parent_mobile', 'second_parent_email', 'second_parent_relation',
    ];

    public function array(): array
    {
        return [[
            'ADM-1001', 'Sara Ahmed', 'Grade 5', 'B', 'female', '2015-04-12', '', 'Qatar', '', '', 'active', '',
            'Ahmed Saleh', '55123456', 'ahmed@example.com', 'father',
            'Mariam Ali', '55987654', '', 'mother',
        ]];
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
