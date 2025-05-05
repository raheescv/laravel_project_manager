<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = Account::query()
            ->when($this->filters['account_type'] ?? '', function ($query, $value) {
                return $query->where('account_type', $value);
            })
            ->when($this->filters['model'] ?? '', function ($query, $value) {
                return $query->where('model', $value);
            });

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Account Type',
            'Name',
            'mobile',
            'email',
            'place',
            'description',
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
            $row->account_type,
            $row->name,
            $row->mobile,
            $row->email,
            $row->place,
            $row->description,
        ];
    }
}
