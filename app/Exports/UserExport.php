<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return User::getFilteredQuery($this->filters)
            ->with(['designation']);
    }

    public function headings(): array
    {
        return [
            '#',
            'Order No',
            'Type',
            'Name',
            'Code',
            'Designation',
            'Email',
            'Mobile',
            'Is Admin',
            'Dob',
            'Doj',
            'Place',
            'Nationality',
            'Allowance',
            'Salary',
            'Hra',
            'Is Active',
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
            $row->order_no,
            $row->type,
            $row->name,
            $row->code,
            $row->designation?->name,
            $row->email,
            $row->mobile,
            $row->is_admin ? 'Yes' : 'No',
            $row->dob,
            $row->doj,
            $row->place,
            $row->nationality,
            $row->allowance,
            $row->salary,
            $row->hra,
            $row->is_active ? 'Yes' : 'No',
        ];
    }
}
