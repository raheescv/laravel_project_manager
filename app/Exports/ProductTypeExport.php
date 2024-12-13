<?php

namespace App\Exports;

use App\Models\ProductType;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductTypeExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return ProductType::query();
    }

    public function headings(): array
    {
        return [
            '#',
            'Name',
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
            $row->name,
        ];
    }
}
