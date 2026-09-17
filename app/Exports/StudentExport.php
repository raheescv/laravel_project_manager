<?php

namespace App\Exports;

use App\Actions\Student\GetBalanceAction;
use App\Exports\Templates\StudentImportTemplate;
use App\Livewire\Student\Table;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Students as filtered on the list screen. Same filter query as the screen
 * (Table::filteredQuery), so the file always matches what was on screen.
 */
class StudentExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return Table::filteredQuery($this->filters)
            ->with(['guardians' => fn ($q) => $q->orderByDesc('guardian_student.is_primary')])
            ->select('accounts.*', 'student_details.admission_no', 'student_details.grade', 'student_details.section', 'student_details.status as student_status', 'student_details.gender', 'student_details.card_uid', 'student_details.card_status')
            ->orderBy('accounts.name');
    }

    /** The import template's columns first, so an export can be edited and imported back. */
    public function headings(): array
    {
        return [...StudentImportTemplate::HEADINGS, 'card_status', 'balance'];
    }

    public function map($row): array
    {
        $parents = $row->guardians->values();
        $parent = fn (int $index, string $field) => $field === 'relation'
            ? $parents->get($index)?->pivot->relation
            : $parents->get($index)?->{$field};

        return [
            $row->admission_no,
            $row->name,
            $row->grade,
            $row->section,
            $row->gender,
            $row->dob,
            $row->id_no,
            $row->nationality,
            $row->mobile,
            $row->email,
            $row->student_status,
            $row->card_uid,
            $parent(0, 'name'),
            $parent(0, 'mobile'),
            $parent(0, 'email'),
            $parent(0, 'relation'),
            $parent(1, 'name'),
            $parent(1, 'mobile'),
            $parent(1, 'email'),
            $parent(1, 'relation'),
            $row->card_uid ? $row->card_status : '',
            (new GetBalanceAction())->execute($row->id),
        ];
    }
}
