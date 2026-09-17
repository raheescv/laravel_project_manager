<?php

namespace App\Exports;

use App\Exports\Templates\StudentImportTemplate;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The rows the import check flagged, laid out as the import template plus what is
 * wrong, so the school can fix them and upload this same file again — its headings
 * match on their own and the two extra columns are ignored.
 */
class StudentImportIssuesExport implements FromArray, WithHeadings, WithStyles
{
    /** @param  list<array>  $entries  StudentImportSheet::review() entries with status "error" */
    public function __construct(private array $entries) {}

    public function array(): array
    {
        return array_map(function (array $entry) {
            $row = [];
            foreach (StudentImportTemplate::HEADINGS as $field) {
                $row[] = $entry['values'][$field] ?? '';
            }
            $row[] = implode(' ', $entry['issues']);
            $row[] = $entry['line'];

            return $row;
        }, $this->entries);
    }

    public function headings(): array
    {
        return [...StudentImportTemplate::HEADINGS, 'issues', 'sheet_row'];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
