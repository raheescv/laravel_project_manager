<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Product::query();
    }

    public function headings(): array
    {
        return [
            'id',
            'Code',
            'Name',
            'Name Arabic',
            'Unit',
            'Department',
            'Main Category',
            'Sub Category',

            'Hsn Code',
            'Tax',

            'Description',
            'Is Selling',

            'Cost',
            'MRP',

            'Pattern',
            'Color',
            'Size',
            'Model',
            'Brand',
            'Part No',

            'Min Stock',
            'Max Stock',
            'Location',
            'Reorder Level',
            'Plu',
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
            $row->unit?->id,
            $row->department?->name,
            $row->mainCategory?->name,
            $row->subCategory?->name,
            $row->hsn_code,
            $row->tax,
            $row->description,
            $row->is_selling ? 'Yes' : 'No',
            $row->cost,
            $row->mrp,
            $row->pattern,
            $row->color,
            $row->size,
            $row->model,
            $row->brand,
            $row->part_no,
            $row->min_stock,
            $row->max_stock,
            $row->location,
            $row->reorder_level,
            $row->plu,
            systemDateTime($row->created_at),
        ];
    }
}
