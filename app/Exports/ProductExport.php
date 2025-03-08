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

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = Product::with('unit', 'department', 'mainCategory', 'subCategory')
            ->product()
            ->when($this->department_id ?? '', function ($query, $value) {
                return $query->where('department_id', $value);
            })
            ->when($this->main_category_id ?? '', function ($query, $value) {
                return $query->where('main_category_id', $value);
            })
            ->when($this->sub_category_id ?? '', function ($query, $value) {
                return $query->where('sub_category_id', $value);
            })
            ->when($this->unit_id ?? '', function ($query, $value) {
                return $query->where('unit_id', $value);
            })
            ->when($this->status ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->is_selling ?? '', function ($query, $value) {
                return $query->where('is_selling', $value);
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

            'Barcode',
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
            $row->barcode,
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
