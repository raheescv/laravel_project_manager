<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServiceExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = Product::with('department', 'mainCategory', 'subCategory')
            ->service()
            ->when($this->department_id ?? '', function ($query, $value) {
                $query->where('department_id', $value);
            })
            ->when($this->main_category_id ?? '', function ($query, $value) {
                $query->where('main_category_id', $value);
            })
            ->when($this->sub_category_id ?? '', function ($query, $value) {
                $query->where('sub_category_id', $value);
            })
            ->when($this->status ?? '', function ($query, $value) {
                $query->where('status', $value);
            });

        return $query;

    }

    public function headings(): array
    {
        return [
            'id',
            'Code',
            'Name',
            'Name Arabic',
            'Department',
            'Main Category',
            'Sub Category',
            'Description',
            'Price',
            'Time',
            'Status',
            'Created At',
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
            $row->code,
            $row->name,
            $row->name_arabic,
            $row->department?->name,
            $row->mainCategory?->name,
            $row->subCategory?->name,
            $row->description,
            $row->mrp,
            $row->time,
            $row->status,
            systemDateTime($row->created_at),
        ];
    }
}
